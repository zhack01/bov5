NEXT SUMMARY REPORT

now lets create a summary report  along with gamereport, lets create a fillament for 

here's the logic from prev system 



 public function summaryReportsFilter(Request $request)

    {

        $value = $request->value;

        $filter = $request->filter;

        $operator = $request->operator;

        $getCurrency = $request->currency;

        $date = $request->date;

        $option = $request->groupBy;

        $dateType = $request->dateType;

        $column = '';

        $currency = Session::get('currency');

        $isClient = 'false';

        $isPartner = 'false';

        $isProvider = 'false';

        $isGames = 'false';

        $isPlayer = 'false';

        $table_name = 'per_player';

        $partition = "";

        $queryParams = [];

        

        $conditions = "";

        $groupBy = '';

        $groupBy1 = "";





        if ($operator != ""){

            $queryParams['operator'] = $operator;

            $conditions .= " AND operator_id = :operator ";

        }



        if ($filter == 'client') {

            $isClient = 'true';

        }

        if ($filter == 'partner') {

            $isPartner = 'true';

        }

        if ($filter == 'provider') {

            $isProvider = 'true';

        }

        if ($filter == 'game') {

            $isGames = 'true';

        }

        if ($filter == 'player') {

            $isPlayer = 'true';

        }

        if ($option != 'none') {

            if ($option == 'client' && $filter != 'client') {

                $isClient = 'true';

            }

            if ($option == 'partner' && $filter != 'partner') {

                $isPartner = 'true';

            }

            if ($option == 'provider' && $filter != 'provider') {

                $isProvider = 'true';

            }

            if ($option == 'game' && $filter != 'game') {

                $isGames = 'true';

            }

            if ($option == 'both') {

                $isClient = 'true';

                $isPartner = 'true';

                $isProvider = 'true';

                $isGames = 'true';

            }

        }

        $perDate = false;

        $notDailyStart = true;

        $notDailyEnd = true;







        $currentDateTime = date('Y-m-d');

        $table_name = "per_player";

        $colRounds = "sum(total_rounds) as rounds";

        switch ($dateType) {

            case 'day':

                $queryParams['date'] = $date;

                $conditions .= " AND DATE(CONVERT_TZ(ts.created_at,'+08:00', '+08:00')) = :date ";

                $partition = Helper::multiplePartition($date, $date);

                $table_name = date('Y-m-d', strtotime($date)) == date('Y-m-d') ? 'per_round' : 'per_player';

                $colRounds = date('Y-m-d', strtotime($date)) == date('Y-m-d') ? "count(round_id) as rounds" : "sum(total_rounds) as rounds";

                break;

            case 'between':

                $queryParams['start'] = $date[0]['start'];

                $queryParams['end'] = $date[0]['end'];

                $date_start = $date[0]['start'];

                $date_end = $date[0]['end'];

                $conditions .= " AND DATE(CONVERT_TZ(ts.created_at,'+08:00', '+08:00')) BETWEEN :start AND :end ";

                $perDate = true;

                // $partition = Helper::multiplePartition($date[0]['start'], $date[0]['end']);

                // Check if the time part of $date_start is '00:00:00'

                if (date("H:i:s", strtotime($date[0]['start'])) == '00:00:00') {

                    $notDailyStart = false;

                }

                // Check if the time part of $date_end is '23:59:59'

                if (date("H:i:s", strtotime($date[0]['end'])) == '23:59:59') {

                    $notDailyEnd = false;

                }

                // Get the current date with a fixed time of 01:00:00

                $currentDateTime = date('Y-m-d') . " 00:00:00";

                $isDate = true;

                // Compare $date_end with the fixed time

                $isDate = false;

                $table_name = "per_round";

                $colRounds = "count(round_id) as rounds";

                // Compare $date_end and $notDaily

                if (env('APP_ENV') != 'stage') {

                    if ($date_start < $currentDateTime || $date_end < $currentDateTime && $notDailyStart == true || $date_end < $currentDateTime && $notDailyEnd == true) {

                        $isDate = true;

                        $table_name = "per_player";

                        $colRounds = "sum(total_rounds) as rounds";

                    }

                }

                break;

            case 'month':

                $queryParams['date'] = $date;

                $conditions .= " AND DATE_FORMAT(CONVERT_TZ(ts.created_at,'+08:00', '+08:00'), '%Y-%m') = :date ";

                $perDate = false;

                break;

            case 'year':

                $queryParams['date'] = $date;

                $conditions .= " AND YEAR(CONVERT_TZ(ts.created_at,'+08:00', '+08:00')) = :date ";

                $perDate = false;

                break;

            default:

                break;

        }









        $groupByDate = "";

        $perDateCol = "";

        $groupBy = "";

        if ($perDate == true) {

            $groupByDate = "date,";

            $groupBy .= "date,";

        }

        if ($dateType == 'month') {

            $perDateCol = " DATE_FORMAT(date, '%M %Y') ";

        }



        if ($isClient == 'true') {

            $column .= "(select client_name from clients where client_id = tbl.client_id) as client_name, ";

            if ($value != '' && $value != 'all' && $filter == 'client') {

                $queryParams[':clientId'] = $value;

                $conditions .= "AND ts.client_id = :clientId ";

            }

            $groupBy .= "tbl.client_id, ";

        }



        if ($isProvider == 'true' || $isPartner == 'true' || $isGames == 'true') {

            $column .= "(select sub_provider_name from sub_providers where sub_provider_id = tbl.provider_id) as provider, ";

            if ($value != '' && $value != 'all' && $filter == 'provider') {

                $queryParams[':providerId'] = $value;

                $conditions .= "AND ts.provider_id = :providerId ";

            }

            $groupBy .= "tbl.provider_id, ";

        }



        if ($isPartner == 'true') {

            $column .= "(select provider_name from providers where provider_id = tbl.partner_id) as partner, ";

            if ($value != '' && $value != 'all' && $filter == 'partner') {

                $queryParams[':partnerId'] = $value;

                $conditions .= "AND ts.provider_id IN (select sub_provider_id from sub_providers where provider_id = :partnerId ) ";

            }

            $groupBy .= "tbl.partner_id, ";

        }







        if ($isGames == 'true') {

            $column .= "(select game_name from games where game_id = tbl.game_id) as game_name, ";

            if ($value != '' && $value != 'all' && $filter == 'game') {

                $queryParams[':gameId'] = $value;

                $conditions .= "AND ts.game_id = :gameId ";

            }

            $groupBy .= "tbl.game_id, ";

        }



        if ($isPlayer == 'true') {

            $column .= "player_id, (select username from players where player_id = tbl.player_id) as username, ";

            if ($value != '' && $value != 'all') {

                $queryParams[':playerId'] = $value;

                $queryParams[':clientPlayerId'] = $value;

                $queryParams[':username'] = $value;

                $conditions .= "AND ts.player_id IN (select player_id from players where player_id = :playerId or client_player_id = :clientPlayerId or username = :username )";

            }

            $groupBy .= "tbl.player_id, ";

        }

        if ($getCurrency != '') {

            if ($getCurrency != 'all') {

                $code = $getCurrency;

                $currency = "'" . $code . "'";

                $conditions .= " and ts.client_id IN (select client_id from clients where default_currency = '$code') ";

                $currencyCol = "tbl.currency";

            } elseif ($getCurrency == 'all') {

                $currency = "default_currency";

                if ($filter != '') {

                    $currencyCol = "tbl.currency";

                    $groupBy1 .= ",currency";

                    $groupBy .= "currency, ";

                } elseif ($filter == '') {

                    if ($option != 'none') {

                        $currencyCol = "tbl.currency";

                        $groupBy .= "currency, ";

                        $groupBy1 .= ",currency";

                    } elseif ($option == 'none') {

                        $currencyCol = "tbl.currency";

                        $groupBy .= "currency, ";

                    }

                }

            }

        } elseif ($getCurrency == '') {

            $currencyCol = "(select code from currencies where code = '" . $currency . "' )";

            $code = $currency;

            $currency = "'" . $code . "'";

        }

        if ($groupBy != "") { // if the groupBy is not empty

            $groupBy = preg_replace('/,\s*$/', '', $groupBy);

            $groupBy = "GROUP BY " . $groupBy;

        }



        if ($dateType == 'between' && $perDate == true && $groupBy == '') {

            $groupBy = "group by " . str_replace(',', '', $groupByDate);

        }



        if ($conditions != "") {

            // Remove the first occurrence of 'AND', including possible leading spaces

            $conditions = preg_replace('/^\s*AND\s+/', '', $conditions);

            

            // Add the 'WHERE' keyword

            $conditions = " WHERE " . $conditions;

        }



        $query = "

            SELECT

                $perDateCol date, $column $currencyCol currency, FORMAT(SUM(bet),4) bet, FORMAT(SUM(win),4) win, FORMAT(SUM(bet-win),4) ggr, FORMAT(SUM(rounds),0) rounds, FORMAT(count(distinct player_id),0) players

            FROM (

                SELECT

                    created_at as date, operator_id, ts.client_id, player_id, (select provider_id from sub_providers where sub_provider_id = ts.provider_id) partner_id, provider_id, game_id, clients.currency currency, sum(bet * rate) bet, sum(win * rate) win, $colRounds

                FROM bo_aggreagate.$table_name $partition ts

                INNER JOIN (SELECT client_id, default_currency AS currency, client_name, (SELECT SUBSTR(convert_list, INSTR(convert_list, $currency) + 16, INSTR(SUBSTR((convert_list), INSTR(convert_list, $currency) + 16, 15), '\"') - 1) FROM currencies_convert_list WHERE currency_code = default_currency) AS rate FROM clients ) AS clients ON ts.client_id = clients.client_id

                $conditions

                group by created_at, ts.client_id, player_id, provider_id, game_id $groupBy1

            ) tbl $groupBy

        ";

        // dd($query, $queryParams);

        $result = DB::select($query, $queryParams);

        if (count($result) > 0) {

            if ($result[0]->date == null || $result[0]->date == '' || $result[0]->date == 'null') {

                $result = [];

            }

        }

        return response()->json($result);

    }



@extends('layouts.page')



@section('title', 'Gaming Summary Report')

@section('links')

<link href="{{ asset('css/yearpicker.css') }}" rel="stylesheet">

@endsection

@section('page_content')

<div class="col-md-12">

    <div class="card">

        <div class="card-header d-flex justify-content-between">

            <h5>Gaming Summary Report</h5>

            <input type="hidden" name="" id="filename" value="GamingSummaryReport">

            <input type="hidden" id="currency" value="{{Session::get('currency')}}">

            <a class="btn btn-secondary btn-sm" href="#" onclick="al.downloadGSReport()"><i id='albtn' class="fa fa-download"></i>&nbsp;Download</a>

        </div>

        <div class="card-body">

            <div class="select-form d-flex justify-content-between col-md-12  " >

                <div class="select-form d-flex  col-md-9 pl-0">

                    <div class="form-group col-md-3" id="_operators_show_" >

                        <label for="">Operators</label>

                        <select name="get_operator" id="get_operator" onchange="execute.getCurrency($(this).val())" class="form-control">

                                <option value=""></option>

                            @foreach($operators as $operator)

                                <option value="{{ $operator->operator_id }}"

                                    @if($operator->status_id != 1)

                                        style="background-color: #ffcccc;"

                                    @endif>

                                    {{ $operator->client_name }}

                                </option>

                            @endforeach

                        </select>

                    </div>



                    <div class="form-group  col-md-3">

                        <label for="">Filter Option: </label>

                        <select name="filter" id="filter" onchange="execute.getFilter($(this).val())" class="form-control">

                            <option value=""></option>

                            <option value="client">Clients</option>

                            <option value="partner">Partners</option>

                            <option value="provider">Providers</option>

                            <option value="game">Games</option>

                            <option value="player">Player</option>

                        </select>

                    </div>



                    <div class="form-group col-md-3 details_show" id="client_show" style="display: none;" >

                        <label for="">Clients</label>

                        <select name="get_client" id="get_client" class="form-control"></select>

                    </div>

                    <div class="form-group col-md-3 details_show" id="partner_show" style="display: none;">

                        <label for="">Partners</label>

                        <select name="get_provider" id="get_partner" class="form-control">

                        </select>

                    </div>

                    <div class="form-group col-md-3 details_show" id="provider_show" style="display: none;">

                        <label for="">Provider</label>

                        <select name="get_provider" id="get_provider" class="form-control"></select>

                    </div>

                    <div class="form-group col-md-3 details_show"  id="game_show" style="display: none;">

                        <label for="">Games</label>

                        <select name="get_game" id="get_game" class="form-control">

                        </select>

                    </div>

                    <div class="form-group col-md-3 details_show"  id="player_show" style="display: none;">



                    </div>

                    <div class="form-group  col-md-3" >

                        <label for="">Currency: </label>

                        <select name="currency" id="get_currency" class="form-control ">



                        </select>

                    </div>

                    <div class="form-group col-md-3" id="_third_option_show_" >

                        <label for="">Display</label>

                        <select name="get_provider" id="groupBy" class="form-control">

                                <option value="none">None</option>

                                <option value="client">Per Client</option>

                                <option value="partner">Per Partner</option>

                                <option value="provider" >Per Provider</option>

                                <option value="game" >Per Game</option>

                                <option value="both">BOTH</option>

                        </select>

                    </div>

                </div>

                <div class="form-group view-button _adjust">

                    <input type="submit"  style="margin-top: 30px;color:#fff;" onclick="execute.submit()" value="View" id="submit" class="btn btn-info form-control">

                </div>



            </div>

            <div class="form-group col-md-9 d-flex">



                <div class="form-group  col-md-3" >

                    <label for="">Filter Date: </label>

                    <select name="filter-date" id="date_filter" onchange="execute.fitlerDate($(this).val())" class="form-control filter-date">

                        <option value="day">Day</option>

                        <option value="between">Between</option>

                        <option value="month">Month</option>

                        <option value="year">Year</option>

                    </select>

                </div>



                <div class="form-group  filter_day col-md-3 date_display" id="filter-days" >

                    <label for="">Day: </label>

                    <input type="date" class="form-control only-date-input-from" value="{{date("Y-m-d")}}" max="{{ date('Y-m')}}" name="" id="get_day" >

                </div>



                <div class="form-group col-md-3 filter_between date_display"  style="display: none;">

                    <label for="">From:</label>

                    <input type="date" class="form-control" id="date-start" value="{{date("Y-m-d")}}" max="{{date("Y-m-d")}}" required >

                </div>

                <div class="form-group col-md-3 filter_between date_display" style="display: none;">

                    <div class="datefrom-margin">

                        <label for="">To:</label>

                        <input type="date" class="form-control " id="date-end" max="{{date("Y-m-d")}}" value="{{date("Y-m-d")}}" required >

                    </div>

                </div>

                <div class="form-group col-md-3 filter_month date_display" id="filter-month" style="display: none;">

                    <label for="">Month:</label>

                    <input type="month" class="form-control" value="{{ date('Y-m')}}" max="{{ date('Y-m')}}" id="get_month">

                </div>

                <div class="form-group col-md-3 filter_year date_display" id="filter-year" style="display: none;">

                    <label for="">Year: </label>

                    {{-- <input type="year" class="yearpicker form-control" value="{{ date('Y')}}" max="{{ date('Y')}}" id="get_year" > --}}

                    <select name="year" id="get_year" class="form-control">

                        @php

                            $currentYear = date('Y');

                            $startingYear = 2020; // Set the starting year

                            for ($year = $currentYear; $year >= $startingYear; $year--) {

                                $selected = ($year == $currentYear) ? 'selected' : '';

                                echo "<option value=\"$year\" $selected>$year</option>";

                            }

                        @endphp

                    </select>

                </div>

            </div>

        </div>

        <div class="card-body">

            <div class="provider-list" id="display_summary">

            </div>

            <div id="loading-modal" class="loading" style="display: none;">

                <div class="loading-box">

                    <p><i class="fa fa-spinner fa-spin fa-3x fa-fw"></i></p>

                </div>

            </div>

        </div>

    </div>

</div>



@endsection

@section('game-report')

<script src="{{ asset('js/yearpicker.js') }}"></script>

<script type="text/javascript">

var url = window.location.origin;

var execute = (()=> {

    return {

        displayFilters: (element,data)=>{

            $('#get_'+element).empty()

            $('#get_'+element).append(`<option value="${element=='currency'?'':'all'}">${element=='currency'?'':'All'}</option>`)

            $('#get_'+element).append(`<option value="${element=='currency'?'all':''}">${element=='currency'?'All Currency':''}</option>`)

            $.each(data, function(i, item) { 

                $('#get_'+element).append(`

                    

                    <option value="${item.id}">${item.name}</option>`) 

            });

        },

        getCurrency: (id)=>{

            $.ajax({

                headers:{ 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },

                type:'post',

                url: url+'/getCurrency',

                data : {id:id},

                dataType : 'json',

                success:function(data){

                    execute.displayFilters('currency',data)

                }

            });

        },

        getFilter: (value)=>{

            $('.details_show').hide()

            if(value == 'player'){

                $('#player_show').empty()

                $('#player_show').append(`

                    <label for="">PlayerID</label>

                    <input type="text" id="get_player" class="form-control">

                `)

                $('#player_show').show()

            }else{

                var id=$('#get_operator').val()

                $.ajax({

                    headers:{ 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },

                    type:'post',

                    url: url+'/get-filter',

                    data : {type:value,id:id},

                    dataType : 'json',

                    success:function(data){

                        execute.displayFilters(value,data)

                        $('#'+value+'_show').show()

                    }

                });

            }

        },

        fitlerDate: (value)=>{

            $('.date_display').hide()

            console.log(value)

            $('.filter_'+value).show()

        },

        submit: ()=>{

            var filter      = $('#filter').val()

            var value       = $('#get_'+filter).val()

            var currency    = $('#get_currency').val()

            var operator    = $('#get_operator').val()

            var date        = getDates($('#date_filter').val())

            var dateType    = $('#date_filter').val()

            var groupBy     = $('#groupBy').val()

            $.ajax({

                headers:{ 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },

                type:'post',

                url: url+'/summary-report',

                data : {value:value,filter:filter,currency:currency,operator:operator,date:date,dateType:dateType,groupBy:groupBy},

                dataType : 'json',

                beforeSend : function(){

                    $('#loading-modal').show();

                },

                success:function(data){

                    displaySummary(filter,currency,groupBy,data)

                    $('#loading-modal').hide();

                }

            });

        },

    } // end return

})(execute)

execute.getCurrency("")

function getDates(filter){

    if(filter == 'between'){

        var date = new Array();

        date.push({start:$('#date-start').val(),end:$('#date-end').val()})

        return date

    }

    if(filter != 'between'){

        var value = $('#get_'+filter).val()

        return value

    }

}

function displaySummary(filter,currency,groupBy,data){

    $('#display_summary').empty();

    $('#display_summary').append(`

        <table id="summaryTable" cellspacing="0" width="100%" class="table ">

            <thead class="bg-light">

                <th class="th-sm" style="width: 55px">Date</th>

                ${filter == 'client' || groupBy == 'client' || groupBy == 'both' ? '<th class="th-sm" style="width: 55px">Client</th>' : ''}

                ${filter == 'partner' || groupBy == 'partner' || groupBy == 'both' ? '<th class="th-sm" style="width: 55px">Partner</th>' : ''}

                ${filter == 'provider' || groupBy == 'provider' || filter == 'game' || groupBy == 'game' || groupBy == 'both' ? '<th class="th-sm" style="width: 55px">Provider</th>' : ''}

                ${filter == 'game' || groupBy == 'game' || groupBy == 'both' ? '<th class="th-sm" style="width: 55px">Game</th>' : ''}

                ${filter == 'player' ? '<th class="th-sm" style="width: 55px">PlayerID</th><th class="th-sm" style="width: 55px">Username</th>' : ''}

                <th class="th-sm" style="width: 55px">Currency</th>

                <th class="th-sm" style="width: 55px">Bet</th>

                <th class="th-sm" style="width: 55px">Win</th>

                <th class="th-sm" style="width: 55px">GGR</th>

                <th class="th-sm" style="width: 55px">Rounds</th>

                ${filter != 'player' ? '<th class="th-sm" style="width: 55px">Players</th>' : ''}

            </thead>

            <tbody id="summary_data">

        `);



        $.each(data, function(i, item) {

            $('#summary_data').append(`

                <tr>

                    <td>${item.date}</td>

                    ${filter == 'client' || groupBy == 'client' || groupBy == 'both' ? '<td>'+item.client_name+'</td>' : ''}

                    ${filter == 'partner' || groupBy == 'partner' || groupBy == 'both' ? '<td>'+item.partner+'</td>' : ''}

                    ${filter == 'provider' || groupBy == 'provider' || filter == 'game' || groupBy == 'game' || groupBy == 'both' ? '<td>'+item.provider+'</td>' : ''}

                    ${filter == 'game' || groupBy == 'game' || groupBy == 'both' ? '<td>'+item.game_name+'</td>' : ''}

                    ${filter == 'player' ? '<td>'+item.player_id+'</td><td>'+item.username+'</td>' : ''}

                    <td>${item.currency}</td>

                    <td>${item.bet}</td>

                    <td>${item.win}</td>

                    <td>${item.ggr}</td>

                    <td>${item.rounds}</td>

                    ${filter != 'player' ? '<td>'+item.players+'</td>' : ''}

                </tr>

            `);

        });



    $('#display_summary').append(`

            </tbody>

        </table>

    `);

    $("#summaryTable").DataTable({

        "lengthMenu": [[10, 25, 100, 500, -1], [10, 25, 100, 500, "All"]]

    });

}

</script>

@endsection

