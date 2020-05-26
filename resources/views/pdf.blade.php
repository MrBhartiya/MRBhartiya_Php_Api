<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>pdf</title>
    <style>
        html { margin: 0px}
        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }
        a{
            color: #5D6975;
            text-decoration: underline;
        }
        body {
            position: relative;
            width: 19cm;
            margin: 0 auto;
            color: #001028;
            background: #FFFFFF;
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-family: Arial;
        }
        header {
            padding: 10px 0;
            padding-bottom: 5px;
        }

        /*#logo {
            text-align: center;
            margin-bottom: 10px;
        }*/

        #logo img {
            width: 90px;

        }
        #logo {
            border-bottom: 1px solid #000000;
            padding-top:10px;
        }

        #project {
            float: left;
        }

        #project span {
            color: #5D6975;
            text-align: right;
            width: 52px;
            margin-right: 10px;
            display: inline-block;
            font-size: 0.8em;
        }

        #company {
            float: right;
            text-align: right;
        }

        #project div,
        #company div {
            white-space: nowrap;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin-bottom: 20px;
            color: #000000;
        }

        table th, table td {
            padding-left: 5px;
        }

        table th {
            padding: 5px 20px;
            color: black;
            white-space: nowrap;
            font-weight: bold;
            font-size: 16px;
        }


        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin-bottom: 20px;
            font-size: 14px;
        }



        table.invoice th,
        table.invoice td {
            text-align: center;
        }

        table.invoice th {
            padding: 5px 20px;

            border-bottom: 1px solid #000000;
            white-space: nowrap;
            font-weight: normal;
        }

        table.invoice .service,
        table.invoice .desc {
            text-align: left;
        }

        table.invoice td {
            padding: 20px;
            text-align: left;
        }

        table.invoice td.service,
        table.invoice td.desc {
            vertical-align: top;
        }

        /*table.invoice td.unit,
        table.invoice td.qty,
        table.invoice td.total {
            font-size: 1.2em;
        }*/

        table.invoice td.grand {
            border-top: 1px solid #000000;
        }
        hr{
            height: 0px;
        }
    </style>
</head>
<body>
<header class="clearfix">
    <div id="logo">
        <img src="{{$companyDetails->logo}}" onerror="this.style.display='https://media-exp1.licdn.com/dms/image/C4E0BAQFVng6DVjLR3A/company-logo_200_200/0?e=2159024400&v=beta&t=U8euvP-lnFZu-TrSj1jrORHE7O26SLzIrmX67-9T3gs'">
        <h1 style="float:right">Receipt</h1>
    </div>
    <br>
    <div style="font-size: 14px;" >
        <table style="width: 50%;float: left;">
            <tr>
                <td><h3>Company Info:</h3></td>
            </tr>
            <tr>
                <td>Mr. Bhartiya</td>
            </tr>
            <tr>
                <td>{{$companyDetails->mobile_no}}</td>
            </tr>
            <tr>
                <td>{{$companyDetails->email_id}}</td>
            </tr>
        </table>
        <table style="width: 50%;float: right;text-align: right;">
            <tr>
                <td><h3>Billed To:</h3></td>
            </tr>
            <tr>
                <td>{{$customerTran->user->name}}</td>
            </tr>
            <tr>
                <td>{{$customerTran->user->mobile_no}}</td>
            </tr>
            <tr>
                <td>{{$customerTran->user->email}}</td>
            </tr>
        </table>
    </div>
</header>
<hr>
<div style="font-size: 14px;">
    <table style="width: 33%;float: left;">
        <tr>
            <td><b>Payment Method:</b></td>
        </tr>
        <tr>
            <td>{{$customerTran->payment_mode}}</td>
        </tr>
    </table>
    <table style="width: 33%;float: left;text-align: center;">
        <tr>
            <td><b>Order ID:</b></td>
        </tr>
        <tr>
            <td>{{$customerTran->order_id}}</td>
        </tr>
    </table>
    <table style="width: 33%;float: left;text-align: right;">
        <tr>
            <td><b>Order Date:</b></td>
        </tr>
        <tr>
            <td>{{date('d-m-Y',strtotime($customerTran->created_at))}}</td>
        </tr>
    </table>
    <br><br><br>
    <table style="width: 100%;">
        <tr>
            <td><b>Transaction Id:</b></td>
        </tr>
        <tr>
            <td>{{$customerTran->transaction_id}}</td>
        </tr>
    </table>
    &nbsp;
</div>
<hr>
<div>
    <h2>Order Details</h2>
    <table class="invoice">
        <thead>
            <tr>
                <th class="desc">Plan Name</th>
                <th class="service">Price</th>
                <th class="service">Quantity</th>
                <th class="service">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="desc">{{$customerTran->subscription->title}}</td>
                <td class="service">{{$customerTran->amount}}</td>
                <td class="unit">1</td>
                <td class="qty">{{$customerTran->amount}}</td>
            </tr>
            <tr>
                <td colspan="3" class="grand total" style="text-align: right">TOTAL</td>
                <td class="grand total">{{$customerTran->amount}} RS</td>
            </tr>
            <tr style="font-size: 16px;">
                <td colspan="3"  style="text-align: right">Status</td>
                <td>
                    @if($customerTran->status==2)
                        Paid
                    @else
                        Unpaid
                     @endif
                </td>
            </tr>
        </tbody>
    </table>
</div>
</body>
</html>