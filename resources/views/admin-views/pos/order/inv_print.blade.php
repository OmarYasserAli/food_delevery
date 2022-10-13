<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title> invoice </title>
        
		<style>
            .grid-container {
                display: flex;
            }

            .grid-container-item {
                flex: 1;
                border: 2px solid yellow;
            
            }

			.invoice-box {
				max-width: 800px;
				margin: auto;
				padding: 20px;
				
				box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
				font-size: 16px;
				line-height: 24px;
				font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
				color: #555;
			}
            .invoice-box-block {
				
				margin: auto;
				
				
				font-size: 16px;
				
				font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
				color: #555;
			}

			.invoice-box table {
				width: 100%;
                font-size: 16px;
				line-height: inherit;
				text-align: left;
			}

			.invoice-box table td {
				padding: 2px;
				vertical-align: top;
                
			}
            .invoice-box.rtl table td {
			
                font-size: 11px;
			}
			.invoice-box table tr td:nth-child(2) {
				text-align: right;
			}

			.invoice-box table tr.top table td {
				padding-bottom: 2px;
			}

			.invoice-box table tr.top table td.title {
				font-size: 20px;
				line-height: 20px;
				color: #333;
			}

			.invoice-box table tr.information table td {
				padding-bottom: 40px;
			}

			.invoice-box table tr.heading td {
				background: #eee;
				border-bottom: 1px solid #ddd;
				font-weight: bold;
			}

			.invoice-box table tr.details td {
				padding-bottom: 20px;
			}

			.invoice-box table tr.item td {
				border-bottom: 1px solid #eee;
			}

			.invoice-box table tr.item.last td {
				border-bottom: none;
			}

			.invoice-box table tr.total td:nth-child(2) {
				border-top: 2px solid #eee;
				font-weight: bold;
			}

			@media only screen and (max-width: 600px) {
				.invoice-box table tr.top table td {
					width: 100%;
					display: block;
					text-align: center;
				}

				.invoice-box table tr.information table td {
					width: 100%;
					display: block;
					text-align: center;
				}
			}

			/** RTL **/
			.invoice-box.rtl {
				direction: rtl;
				font-family: Tahoma, 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
			}

			.invoice-box.rtl table {
				text-align: right;
                
			}

			.invoice-box.rtl table tr td:nth-child(2) {
				
			}
		</style>
	</head>

	<body >
        <div style="width:410px;">
        @if ($order->store)
        <div class="invoice-box ">

            <div class="text-break" style="text-align: center; font-weight:bold;">{{$order->store->name}}</div>
            <div class="" style="font-weight: lighter; text-align: center;">
                {{$order->store->address}}
            </div>
            <div style="font-size: 16px;font-weight: lighter;line-height: 0 ;text-align: center;">
                Phone : {{$order->store->phone}}
            </div>
            @if($order->store->gst_status)
            <div style="font-size: 12px;font-weight: lighter;line-height: 1;text-align: center;">
                Gst No : {{$order->store->gst_code}}
            </div>
            @endif
        </div>

        <hr style="width:90%">    
    @endif
</div>

  <table class="" style="width: 95%; padding: 0 3px;">
    <tr>
        <td>
            <div style="font-weight: lighter; font-size:10px;">
                {{date('d/M/Y '.config('timeformat'),strtotime($order['created_at']))}}
            </div>
        </td>
        <td style="font-weight: lighter; font-size:12px; text-align: right;">
            <div >
                {{translate('messages.order_id')}} : {{$order['id']}}</div>
        </td>
    </tr>
  </table>

<div class="invoice-box.rtl " style="padding: 0 10xp;">
    @php($address = json_decode($order->delivery_address, true))
    @if(!empty($address))

        <h5>
            اسم جهة الاتصال  : {{isset($address['contact_person_name'])?$address['contact_person_name']:''}}
        </h5>
        <h5>
            هاتف  : {{isset($address['contact_person_number'])? $address['contact_person_number'] : ''}}
        </h5>
        <h5>
            دار  : {{isset($address['floor'])? $address['floor'] : ''}}
        </h5>
        <h5>
            الشقة : {{isset($address['road'])? $address['road'] : ''}}
        </h5>
        <h5>
            بلوك  : {{isset($address['house'])? $address['house'] : ''}}
        </h5>

        <h5>
            عمارة  : {{isset($address['flat'])? $address['flat'] : ''}}
        </h5>
    @endif
    <h5 class="text-break">
        زون  : {{isset($order->delivery_address)?json_decode($order->delivery_address, true)['address']:''}}
    </h5>
</div>
@if($order->customer)
<div class="col-12 text-break">
    <h5>
        {{translate('messages.customer_name')}} : {{$order->customer['f_name'].' '.$order->customer['l_name']}}
    </h5>
    <h5>
        {{translate('messages.phone')}} : {{$order->customer['phone']}}
    </h5>
    <h5 class="text-break">
        {{translate('messages.address')}} : {{isset($order->delivery_address)?json_decode($order->delivery_address, true)['address']:''}}
    </h5>
</div>
@endif


   <hr style="width:90%;">
    <div class="invoice-box.rtl">
    <table class=" " style="width: 98%; font-size:12px;">
        <thead>
        <tr>
            <th style="">{{translate('messages.qty')}}</th>
            <th class="">{{translate('DESC')}}</th>
            <th class="">{{translate('messages.price')}}</th>
        </tr>
        </thead>

        <tbody>
        @php($sub_total=0)
        @php($total_tax=0)
        @php($total_dis_on_pro=0)
        @php($add_ons_cost=0)
        @foreach($order->details as $detail)
            @if($detail->item)
                <tr>
                    <td class="">
                        {{$detail['quantity']}}
                    </td>
                    <td class="text-break">
                        {{$detail->item['name']}} <br>
                        @if(count(json_decode($detail['variation'],true))>0)
                            <strong><u>Variation : </u></strong>
                            @foreach(json_decode($detail['variation'],true)[0] as $key1 =>$variation)
                                @if ($key1 != 'stock')
                                    <div class="font-size-sm text-body">
                                        <span>{{$key1}} :  </span>
                                        <span class="font-weight-bold">{{$key1=='price'?\App\CentralLogics\Helpers::format_currency($variation):$variation}}</span>
                                    </div>
                                @endif
                            @endforeach
                        @else
                        <div class="font-size-sm text-body">
                            <span>{{'Price'}} :  </span>
                            <span class="font-weight-bold">{{\App\CentralLogics\Helpers::format_currency($detail->price)}}</span>
                        </div>
                        @endif

                        @foreach(json_decode($detail['add_ons'],true) as $key2 =>$addon)
                            @if($key2==0)<strong><u>Addons : </u></strong>@endif
                            <div class="font-size-sm text-body">
                                <span class="text-break">{{$addon['name']}} :  </span>
                                <span class="font-weight-bold">
                                    {{$addon['quantity']}} x {{\App\CentralLogics\Helpers::format_currency($addon['price'])}}
                                </span>
                            </div>
                            @php($add_ons_cost+=$addon['price']*$addon['quantity'])
                        @endforeach
                    </td>
                    <td style="width: 28%">
                        @php($amount=($detail['price'])*$detail['quantity'])
                        {{\App\CentralLogics\Helpers::format_currency($amount)}}
                    </td>
                </tr>
                @php($sub_total+=$amount)
                @php($total_tax+=$detail['tax_amount']*$detail['quantity'])

            @elseif($detail->campaign)
                <tr>
                    <td class="">
                        {{$detail['quantity']}}
                    </td>
                    <td class="text-break">
                        {{$detail->campaign['title']}} <br>
                        @if(count(json_decode($detail['variation'],true))>0)
                            <strong><u>Variation : </u></strong>
                            @foreach(json_decode($detail['variation'],true)[0] as $key1 =>$variation)
                                <div class="font-size-sm text-body">
                                    <span>{{$key1}} :  </span>
                                    <span class="font-weight-bold">{{$key1=='price'?\App\CentralLogics\Helpers::format_currency($variation):$variation}}</span>
                                </div>
                            @endforeach
                        @else
                        <div class="font-size-sm text-body">
                            <span>{{'Price'}} :  </span>
                            <span class="font-weight-bold">{{\App\CentralLogics\Helpers::format_currency($detail->price)}}</span>
                        </div>
                        @endif

                        @foreach(json_decode($detail['add_ons'],true) as $key2 =>$addon)
                            @if($key2==0)<strong><u>Addons : </u></strong>@endif
                            <div class="font-size-sm text-body">
                                <span class="text-break">{{$addon['name']}} :  </span>
                                <span class="font-weight-bold">
                                                {{$addon['quantity']}} x {{\App\CentralLogics\Helpers::format_currency($addon['price'])}}
                                            </span>
                            </div>
                            @php($add_ons_cost+=$addon['price']*$addon['quantity'])
                        @endforeach
                    </td>
                    <td style="width: 28%">
                        @php($amount=($detail['price'])*$detail['quantity'])
                        {{\App\CentralLogics\Helpers::format_currency($amount)}}
                    </td>
                </tr>
                @php($sub_total+=$amount)
                @php($total_tax+=$detail['tax_amount']*$detail['quantity'])
            @endif
        @endforeach
        </tbody>
    </table>
</div>
   <hr style="width: 90%">
   <div class="invoice-box.rtl">
   <table class=" " style="width: 98%; font-size:18px !important;">
    <tr><td>{{translate('messages.item_price')}}:</td><td>{{\App\CentralLogics\Helpers::format_currency($sub_total)}}</td></tr>
    <tr><td>{{translate('messages.Delivery address')}}:</td><td>{{\App\CentralLogics\Helpers::format_currency($sub_total)}}</td></tr>
    <tr><td>{{translate('messages.discount')}}:</td><td>- {{\App\CentralLogics\Helpers::format_currency($order['store_discount_amount'])}}</td></tr>
    <tr><td>{{translate('messages.coupon_discount')}}</td><td>- {{\App\CentralLogics\Helpers::format_currency($order['coupon_discount_amount'])}}</td></tr>
    <tr ><td style="border-bottom: solid; border-bottom-width: 0px; padding-bottom:15px;">{{translate('messages.delivery_charge')}}:</td>
        <td style="border-bottom: solid; border-bottom-width: 1px;"> @php($del_c=$order['delivery_charge'])
        {{\App\CentralLogics\Helpers::format_currency($del_c)}}
        </td></tr>
        
        <tr><td>{{translate('messages.total')}}:</td><td> {{\App\CentralLogics\Helpers::format_currency($sub_total+$del_c+$order['total_tax_amount']+$add_ons_cost-$order['coupon_discount_amount'] - $order['store_discount_amount'])}}</td></tr>
        
    
    </table>
    <div class="invoice-box-block" style="font-size: 12px; text-align:center;">
        <span style="font-size: 12px;">{{translate('Paid by')}}: {{$order->payment_method}}</span>	<span>{{translate('messages.amount')}}: {{$order->order_amount + $order->adjusment}}</span>	<span>{{translate('messages.change')}}: {{$order->adjusment}}</span>
    </div>
  

</div>
    
   
    <hr style="width:90%;">
    <div class="invoice-box-block" style="font-size: 12px; text-align:center;">
        """{{translate('messages.THANK YOU')}}"""
    </div>
    <hr style="width:90%;">


		{{-- <div class="invoice-box">
			<table cellpadding="0" cellspacing="0">
				<tr class="top">
					<td colspan="2">
						<table>
							<tr>
								<td class="title">
								</td>

								<td style="align-items: center">
									  سنتر الامل <br />
                                      مدينة الامل السكنية<br />
                                      Phone : 07700000000
								</td>
							</tr>
						</table>
					</td>
				</tr>

				<tr class="information">
					<td colspan="2">
						<table>
							<tr>
								<td>
									Sparksuite, Inc.<br />
									12345 Sunny Road<br />
									Sunnyville, CA 12345
								</td>

								<td>
									Acme Corp.<br />
									John Doe<br />
									john@example.com
								</td>
							</tr>
						</table>
					</td>
				</tr>

				<tr class="heading">
					<td>Payment Method</td>

					<td>Check #</td>
				</tr>

				<tr class="details">
					<td>Check</td>

					<td>1000</td>
				</tr>

				<tr class="heading">
					<td>Item</td>

					<td>Price</td>
				</tr>

				<tr class="item">
					<td>Website design</td>

					<td>$300.00</td>
				</tr>

				<tr class="item">
					<td>Hosting (3 months)</td>

					<td>$75.00</td>
				</tr>

				<tr class="item last">
					<td>Domain name (1 year)</td>

					<td>$10.00</td>
				</tr>

				<tr class="total">
					<td></td>

					<td>Total: $385.00</td>
				</tr>
			</table>
		</div> --}}
	</body>
</html>