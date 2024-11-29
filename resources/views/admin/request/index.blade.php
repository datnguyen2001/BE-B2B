@extends('admin.layout.index')
<style>
    .content__product:nth-child(2n) {
        background: #E0E0E0;
    }
</style>
@section('main')
    <main id="main" class="main">

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title">{{$titlePage}}</h5>
                            </div>
                            <div class="d-flex justify-content-end">
                                <form class="d-flex align-items-center w-50" method="get"
                                      action="{{route('admin.request.approved-not')}}">
                                    <input name="key_search" type="text" value="{{request()->get('key_search')}}"
                                           placeholder="Tìm kiếm tên yêu cầu" class="form-control" style="margin-right: 16px">
                                    <button class="btn btn-info" style="padding-top: 10px;padding-bottom: 10px"><i class="bi bi-search"></i></button>
                                    <a href="{{route('admin.request.approved-not')}}" class="btn btn-danger" style="margin-left: 15px">Hủy </a>
                                </form>
                            </div>
                            <div class="card-body p-3">
                                <div class="overflow-auto">
                                    @if(count($listData) > 0)
                                    <div>
                                        <div class="d-flex w-100 align-items-center text-center text-white font-size-16" style="background: #4154f1;padding: 5px 0">
                                            <div style="width: 20%">Hình ảnh</div>
                                            <div style="width: 40%">Tên yêu cầu </div>
                                            <div style="width: 30%" class="text-start">Thông tin người đăng yêu cầu</div>
                                            <div style="width: 10%">Thao tác</div>
                                        </div>
                                        <div class="w-100 bg-white mt-10">
                                            @foreach($listData as $item)
                                                <div class="w-100 d-flex mb-5 pt-2 pb-2 content__product align-items-center">
                                                    <div style="width: 20%" class="d-flex justify-content-center">
                                                        <img src="{{asset(@$item->src[0])}}" alt="" class="w-75 mr-3" style="border-radius: 4px">
                                                    </div>
                                                    <div style="width: 40%" class="d-flex justify-content-center">
                                                        <p>{{$item->name}}</p>
                                                    </div>
                                                    <div style="width: 30%" class="d-flex justify-content-center flex-column">
                                                        <p class="mb-1">{{@$item->user->name}}</p>
                                                        <p>SĐT: {{@$item->user->phone}}</p>
                                                    </div>
                                                    <div style="width: 10%" class="d-flex justify-content-center flex-column">
                                                        <div class="btn-group mb-2">
                                                            <a href="{{url('admin/request/detail/'.$item->id)}}" class="btn btn-icon btn-secondary btn-hover-success btn-sm mx-2" data-bs-toggle="tooltip" data-bs-placement="top" title="" data-bs-original-title="Xem chi tiết">
                                                                <i class="bi bi-eye "></i>
                                                            </a>
                                                            <a href="{{url('admin/request/delete/'.$item->id)}}" class="btn btn-delete btn-icon btn-secondary btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="" data-bs-original-title="Xóa">
                                                                <i class="bi bi-trash "></i>
                                                            </a>
                                                        </div>
                                                        <a href="{{url('admin/request/status/1/'.$item->id)}}" type="reset" class="btn btn-success">Duyệt yêu cầu</a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
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
                    action: function(){
                        location.href = this.$target.attr('href');
                    }
                },
                close: {
                    text: 'Hủy',
                    action: function () {}
                }
            }
        });
    </script>
@endsection
