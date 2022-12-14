<div style="width:410px;">
    @if ($order->store)
        <div class="text-center pt-4 mb-3">

            <h2 class="text-break" style="line-height: 1">{{$order->store->name}}</h2>
            <h5 class="text-break" style="font-size: 20px;font-weight: lighter;line-height: 1">
                {{$order->store->address}}
            </h5>
            <h5 style="font-size: 16px;font-weight: lighter;line-height: 1">
                Phone : {{$order->store->phone}}
            </h5>
            @if($order->store->gst_status)
            <h5 style="font-size: 12px;font-weight: lighter;line-height: 1">
                Gst No : {{$order->store->gst_code}}
            </h5>
            @endif
        </div>

        <span>---------------------------------------------------------------------------------</span>
    @endif

    <div class="row mt-3">
        <div class="col-6">
            <h5>{{translate('messages.order_id')}} : {{$order['id']}}</h5>

        </div>
        <div class="col-6">
            <h5 style="font-weight: lighter">
                {{date('d/M/Y '.config('timeformat'),strtotime($order['created_at']))}}
            </h5>
        </div>
        <div class="col-12">
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
    </div>
    <h5 class="text-uppercase"></h5>
    <span>---------------------------------------------------------------------------------</span>
    <table class="table mt-3" style="width: 98%; color:#000000;">
        <thead>
        <tr>
            <th style="width: 10%">{{translate('messages.qty')}}</th>
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
    <span>---------------------------------------------------------------------------------</span>
    <div class="row justify-content-md-end">
        <div class="col-md-7 col-lg-7">
            <dl class="row text-right">
                <dt class="col-6">{{translate('messages.item_price')}}:</dt>
                <dd class="col-6">{{\App\CentralLogics\Helpers::format_currency($sub_total)}}</dd>
                <dt class="col-6">{{translate('messages.Delivery address')}}:</dt>
                <dd class="col-6">{{\App\CentralLogics\Helpers::format_currency($sub_total)}}</dd>

                <dt class="col-6">{{translate('messages.discount')}}:</dt>
                <dd class="col-6">
                    - {{\App\CentralLogics\Helpers::format_currency($order['store_discount_amount'])}}</dd>
                <dt class="col-6">{{translate('messages.coupon_discount')}}:</dt>
                <dd class="col-6">
                    - {{\App\CentralLogics\Helpers::format_currency($order['coupon_discount_amount'])}}</dd>
                <dt class="col-6">{{translate('messages.delivery_charge')}}:</dt>
                <dd class="col-6">
                    @php($del_c=$order['delivery_charge'])
                    {{\App\CentralLogics\Helpers::format_currency($del_c)}}
                    <hr>
                </dd>

                <dt class="col-6" style="font-size: 20px">{{translate('messages.total')}}:</dt>
                <dd class="col-6" style="font-size: 20px">{{\App\CentralLogics\Helpers::format_currency($sub_total+$del_c+$order['total_tax_amount']+$add_ons_cost-$order['coupon_discount_amount'] - $order['store_discount_amount'])}}</dd>



            </dl>
        </div>
    </div>
    <div class="d-flex flex-row justify-content-between border-top">
        <span>{{translate('Paid by')}}: {{$order->payment_method}}</span>	<span>{{translate('messages.amount')}}: {{$order->order_amount + $order->adjusment}}</span>	<span>{{translate('messages.change')}}: {{$order->adjusment}}</span>
    </div>
    <span>---------------------------------------------------------------------------------</span>
    <h5 class="text-center pt-3">
        """{{translate('messages.THANK YOU')}}"""
    </h5>
    <span>---------------------------------------------------------------------------------</span>
</div>
