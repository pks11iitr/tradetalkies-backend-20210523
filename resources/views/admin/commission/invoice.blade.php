<table>
    <thead>
    <tr>
        <th>Shopper Name</th>
        <th>OrderId</th>
        <th>Delivery Charge</th>
        <th>Commission Amount</th>
        <th>Total</th>
        <th>Date</th>
    </tr>
    </thead>
    <tbody>
    @foreach($historyobj as $commission)
        <tr>
            <td>{{$commission->shoppr->name??''}}</td>
            <td>{{$commission->refid??''}}</td>
            <td>{{$commission->rider_delivery_charge??0}}</td>
            <td>{{$commission->rider_commission??0}}</td>
            <td>{{($commission->rider_commission??0)+($commission->rider_delivery_charge??0)}}</td>
            <td>{{$commission->created_at??''}}</td>
        </tr>

    @endforeach
    </tbody>
</table>
