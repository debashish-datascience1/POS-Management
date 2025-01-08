@extends('layouts.app')

@section('title', __('Pay Salary'))

@section('content')

<section class="content-header">
    <h1>@lang('Pay Salary')
        <small>@lang('Manage Salary Payments')</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('All Salary Payments')])
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="salary_payment_table">
                <thead>
                    <tr>
                        <th>@lang('SNo')</th>
                        <th>@lang('Employee Name')</th>
                        <th>@lang('Salary')</th>
                        <th>@lang('Advance')</th>
                        <th>@lang('Last Paid Month')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $index => $user)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ number_format($user->salary ?? 0, 2) }}</td>
                            <td>{{ number_format($user->advance ?? 0, 2) }}</td>
                            <td>{{ $user->salaryPayments->first()?->payment_date?->format('Y-m-d') ?? 'N/A' }}</td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                            data-toggle="dropdown" aria-expanded="false">
                                        @lang('Pay Salary') <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right" role="menu" 
                                        style="width: 350px; padding: 15px;">
                                        <li>
                                            <form action="{{ route('pay_salary.store', $user->id) }}" method="POST">
                                                @csrf
                                                <div class="form-group">
                                                    <label for="name">@lang('Employee Name')</label>
                                                    <input type="text" class="form-control" id="name" 
                                                           value="{{ $user->name }}" readonly>
                                                </div>

                                                <div class="form-group">
                                                    <label for="salary">@lang('Basic Salary')</label>
                                                    <input type="number" class="form-control" id="salary" 
                                                           name="salary" value="{{ $user->salary ?? 0 }}" readonly>
                                                </div>

                                                <div class="form-group">
                                                    <label for="month">@lang('Month')</label>
                                                    <select class="form-control" id="month" name="month" required>
                                                        @foreach(range(1, 12) as $month)
                                                            <option value="{{ $month }}" {{ date('n') == $month ? 'selected' : '' }}>
                                                                {{ date('F', mktime(0, 0, 0, $month, 1)) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label for="year">@lang('Year')</label>
                                                    <select class="form-control" id="year" name="year" required>
                                                        @foreach(range(date('Y')-2, date('Y')+1) as $year)
                                                            <option value="{{ $year }}" {{ date('Y') == $year ? 'selected' : '' }}>
                                                                {{ $year }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label for="working_days">@lang('Working Days')</label>
                                                    <input type="number" class="form-control" id="working_days" 
                                                           name="working_days" required min="0" max="31">
                                                </div>

                                                <div class="form-group">
                                                    <label for="advance">@lang('Advance')</label>
                                                    <input type="number" class="form-control" id="advance" 
                                                           name="advance" value="{{ $user->advance ?? 0 }}" readonly>
                                                </div>

                                                <div class="form-group">
                                                    <label for="net_payment">@lang('Net Payment')</label>
                                                    <input type="number" class="form-control" id="net_payment" 
                                                           name="net_payment" readonly>
                                                </div>

                                                <div class="form-group">
                                                    <label for="payment_mode">@lang('Payment Mode')</label>
                                                    <select class="form-control" id="payment_mode" name="payment_mode" required>
                                                        <option value="cash">@lang('Cash')</option>
                                                        <option value="bank">@lang('Bank Transfer')</option>
                                                        <option value="cheque">@lang('Cheque')</option>
                                                    </select>
                                                </div>

                                                <div class="form-group" style="margin-top: 10px;">
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        @lang('Pay Salary')
                                                    </button>
                                                    <button type="button" class="btn btn-default btn-sm" 
                                                            onclick="$(this).closest('.dropdown-menu').removeClass('show')">
                                                        @lang('messages.close')
                                                    </button>
                                                </div>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" style="text-align:right">@lang('Total'):</th>
                        <th id="total_salary"></th>
                        <th id="total_advance"></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endcomponent
</section>

@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        var salary_table = $('#salary_payment_table').DataTable({
            processing: true,
            serverSide: false,
            columns: [
                { data: 'SNo', name: 'SNo' },
                { data: 'name', name: 'name' },
                { data: 'salary', name: 'salary' },
                { data: 'advance', name: 'advance' },
                { data: 'last_paid', name: 'last_paid' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                
                // Calculate totals for salary and advance columns
                var totalSalary = api.column(2).data().reduce(function (a, b) {
                    return parseFloat(a) + parseFloat(b);
                }, 0);
                
                var totalAdvance = api.column(3).data().reduce(function (a, b) {
                    return parseFloat(a) + parseFloat(b);
                }, 0);
                
                // Update footer
                $('#total_salary').html(totalSalary.toFixed(2));
                $('#total_advance').html(totalAdvance.toFixed(2));
            }
        });

        // Calculate net payment when working days change
        $(document).on('change', '#working_days', function() {
            var workingDays = parseFloat($(this).val()) || 0;
            var salary = parseFloat($(this).closest('form').find('#salary').val()) || 0;
            var advance = parseFloat($(this).closest('form').find('#advance').val()) || 0;
            
            // Calculate per day salary (assuming 30 days month)
            var perDaySalary = salary / 30;
            var totalSalary = perDaySalary * workingDays;
            var netPayment = totalSalary - advance;
            
            $(this).closest('form').find('#net_payment').val(netPayment.toFixed(2));
        });

        // Handle dropdown click
        $('.dropdown-toggle').on('click', function(e) {
            e.preventDefault();
            $(this).next('.dropdown-menu').toggleClass('show');
        });

        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.btn-group').length) {
                $('.dropdown-menu').removeClass('show');
            }
        });
    });
</script>
@endsection

