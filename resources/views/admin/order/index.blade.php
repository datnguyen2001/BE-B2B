@extends('admin.layout.index')
@section('main')
    <main id="main" class="main">
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body d-flex align-items-center flex-wrap gap-3"
                             style="padding-top: 20px">
                            <a href="{{url('admin/order/index/all')}}" type="button"
                               class="btn btn-outline-secondary mb @if($status == 'all') active @endif"> Tất cả đơn hàng
                                <span style="font-weight: 700">{{$order_all}}</span></a>
                            <a href="{{url('admin/order/index/0')}}"
                               class="btn btn-outline-warning @if($status == 0) active @endif">Chờ xác nhận <span
                                    style="font-weight: 700">{{$order_pending}}</span></a>
                            <a href="{{url('admin/order/index/1')}}" type="button"
                               class="btn btn-outline-info @if($status == 1) active @endif">Chờ lấy hàng <span
                                    style="font-weight: 700">{{$order_confirm}}</span></a>
                            <a href="{{url('admin/order/index/2')}}" type="button"
                               class="btn btn-outline-primary @if($status == 2) active @endif">Đang vận chuyển <span
                                    style="font-weight: 700">{{$order_delivery}}</span></a>
                            <a href="{{url('admin/order/index/3')}}" type="button"
                               class="btn btn-outline-success @if($status == 3) active @endif">Đơn hàng hoàn thành <span
                                    style="font-weight: 700">{{$order_complete}}</span></a>
                            <a href="{{url('admin/order/index/4')}}" type="button"
                               class="btn btn-outline-danger  @if($status == 4) active @endif">Đơn huỷ <span
                                    style="font-weight: 700">{{$order_cancel}}</span></a>
                            <a href="{{url('admin/order/index/5')}}" type="button"
                               class="btn btn-outline-danger  @if($status == 5) active @endif">Trả hàng hoàn tiền <span
                                    style="font-weight: 700">{{$return_refund}}</span></a>
                        </div>
                    </div>

                    <div class="card" >
                        <div class="card-body d-flex justify-content-end" style="padding: 20px">
                            <form class="d-flex align-items-center w-50" method="get"
                                  action="{{url('admin/order/index/'.$status)}}">
                                <input name="search" type="text" value="{{request()->get('search')}}"
                                       placeholder="Tìm kiếm..." class="form-control" style="margin-right: 16px">
                                <button class="btn btn-info" style="margin-left: 15px"><i class="bi bi-search"></i>
                                </button>
                                <a href="{{url('admin/order/index/all')}}" class="btn btn-danger"
                                   style="margin-left: 15px">Hủy </a>
                            </form>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title">{{$titlePage}}</h5>
                            </div>
                            @if(count($listData) > 0)
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th scope="col">ID</th>
                                        <th scope="col">Mã đơn</th>
                                        <th scope="col">Bên nhận</th>
                                        <th scope="col" style="width: 12%;">Tổng tiền</th>
                                        <th scope="col" style="width: 15%;">Mã vận chuyển</th>
                                        @if($status == 0 || $status == 'all' || $status == 1 || $status == 2 || $status == 3)
                                            <th scope="col" style="width: 15%;">Xác nhận nhanh</th>
                                        @endif
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($listData as $k => $value)
                                        <tr>
                                            <th id="{{$value->id}}" scope="row">{{$k+1}}</th>
                                            <td>
                                                <a href="{{url('admin/order/detail/'.$value->id)}}"
                                                   class="btn btn-icon btn-light btn-hover-success btn-sm"
                                                   data-bs-toggle="tooltip" data-bs-placement="top" title=""
                                                   data-bs-original-title="Chi tiết đơn hàng">
                                                    {{$value->order_code}}<br>
                                                    <span
                                                        style="color: @if($value->status == 0) #FF9900 @elseif($value->status == 1) #0099FF @elseif($value->status == 2) #0066FF @elseif($value->status == 3) #00FF00 @elseif($value->status == 4) #FF3333 @endif; font-weight: 600">{{$value->status_name}}</span><br>
                                                    <span> @if($value->type_payment == 1) Nhận hàng thanh toán @else
                                                            Thanh toán qua VNPAY @endif</span>
                                                    <br>{{$value->created_at}}
                                                </a>
                                            </td>
                                            <td>
                                                {{$value->name}}<br>
                                                {{$value->phone}}<br>
                                                {{$value->full_address}}
                                            </td>

                                            <td>
                                                {{number_format($value->total_payment)}} đ
                                            </td>
                                            <td>
                                                @if($value->order_code_transport)
                                                    {{$value->order_code_transport}}
                                                @else
                                                    Không có
                                                @endif
                                            </td>
                                            <td style="border-top: 1px solid #cccccc">
                                                @if($value->status == 0)
                                                    <a href="{{url('admin/order/status/'.$value->id.'/1')}}">
                                                        <button type="submit" class="btn btn-primary mb-2">Xác nhận đơn
                                                        </button>
                                                    </a>
                                                    <a href="{{url('admin/order/status/'.$value->id.'/4')}}">
                                                        <button type="submit" class="btn btn-danger">Huỷ đơn hàng
                                                        </button>
                                                    </a>
                                                @elseif($value->status == 1)
                                                    <a href="{{url('admin/order/status/'.$value->id.'/2')}}">
                                                        <button type="submit" class="btn btn-primary mb-2">Giao hàng
                                                        </button>
                                                    </a>
                                                    <a href="{{url('admin/order/status/'.$value->id.'/4')}}">
                                                        <button type="submit" class="btn btn-danger">Huỷ đơn hàng
                                                        </button>
                                                    </a>
                                                @elseif($value->status == 2)
                                                    <a href="{{url('admin/order/status/'.$value->id.'/3')}}">
                                                        <button type="submit" class="btn btn-primary">Hoàn thành đơn
                                                        </button>
                                                    </a>
                                                    <a href="{{url('admin/order/status/'.$value->id.'/4')}}">
                                                        <button type="submit" class="btn btn-danger">Hủy đơn hàng
                                                        </button>
                                                    </a>
                                                @endif
                                                @if($value->status != 4 && $value->status != 0 && $value->status != 5 && $value->status != 3 && $value->type_payment == 2)
                                                        <a href="{{url('admin/order/status/'.$value->id.'/5')}}">
                                                            <button type="submit" class="btn btn-danger mt-2">Trả hàng hoàn tiền
                                                            </button>
                                                        </a>
                                                    @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                                <div class="d-flex justify-content-center">
                                    {{ $listData->appends(request()->all())->links('admin.pagination_custom.index') }}
                                </div>
                            @else
                                <h5 class="card-title">Không có dữ liệu</h5>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
@section('script')
    <script>
        $('a.btn-delete').confirm({
            title: 'Xác nhận!',
            content: 'Bạn có chắc chắn muốn xóa bản ghi này?',
            buttons: {
                ok: {
                    text: 'Xóa',
                    btnClass: 'btn-danger',
                    action: function () {
                        location.href = this.$target.attr('href');
                    }
                },
                close: {
                    text: 'Hủy',
                    action: function () {
                    }
                }
            }
        });
    </script>
@endsection
