@extends('admin.layout.index')
@section('title', 'Cài đặt')

@section('style')

@endsection

@section('main')
    <main id="main" class="main d-flex flex-column justify-content-center">
        <div class="">
            <h1 class="h3 mb-4 text-gray-800">{{$titlePage}}</h1>
            <hr>
            <form action="{{ route('admin.setting.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row mt-3">
                    <div class="col-2">Logo:</div>
                    <div class="col-6">
                        @if(@$data->logo != null)
                            <div class="form-control position-relative div-parent" style="padding-top: 50%">
                                <div class="position-absolute w-100 h-100 div-file" style="top: 0; left: 0;z-index: 10">
                                    <button type="button" class="position-absolute clear border-0 bg-danger p-0 d-flex justify-content-center align-items-center" style="top: -10px;right: -10px;width: 30px;height: 30px;border-radius: 50%"><i class="bi bi-x-lg text-white"></i></button>
                                    <img src="{{asset(@$data->logo)}}" class="w-100 h-100" style="object-fit: cover">
                                </div>
                            </div>
                        @else
                            <div class="form-control position-relative div-parent" style="padding-top: 50%">
                                <button type="button" class="position-absolute border-0 bg-transparent select-image" style="top: 50%;left: 50%;transform: translate(-50%,-50%)">
                                    <i style="font-size: 30px" class="bi bi-download"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-2">Hotline :</div>
                    <div class="col-10">
                        <input class="form-control" name="hotline" value="{{@$data->hotline}}" type="text" >
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-2">Email hỗ trợ khách hàng:</div>
                    <div class="col-10">
                        <input class="form-control" name="customer_support_email" value="{{@$data->customer_support_email}}" type="text" >
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-2">Email hỗ trợ kỹ thuật :</div>
                    <div class="col-10">
                        <input class="form-control" name="technical_support_email" value="{{@$data->technical_support_email}}" type="text" >
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-2">Email :</div>
                    <div class="col-10">
                        <input class="form-control" name="email" value="{{@$data->email}}" type="text" >
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-2">Facebook :</div>
                    <div class="col-10">
                        <input class="form-control" name="facebook" value="{{@$data->facebook}}" type="text" >
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-2">Twitter :</div>
                    <div class="col-10">
                        <input class="form-control" name="twitter" value="{{@$data->twitter}}" type="text" >
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-2">Zalo :</div>
                    <div class="col-10">
                        <input class="form-control" name="zalo" value="{{@$data->zalo}}" type="text" >
                    </div>
                </div>
                <input type="file" name="file" accept="image/x-png,image/gif,image/jpeg" hidden>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </form>
        </div>

    </main>
@endsection
@section('script')
    <script>
        let parent;
        $(document).on("click", ".select-image", function () {
            $('input[name="file"]').click();
            parent = $(this).parent();
        });
        $('input[name="file"]').change(function(e){
            imgPreview(this);
        });
        function imgPreview(input) {
            let file = input.files[0];
            let mixedfile = file['type'].split("/");
            let filetype = mixedfile[0]; // (image, video)
            if(filetype == "image"){
                let reader = new FileReader();
                reader.onload = function(e){
                    $("#preview-img").show().attr("src", );
                    let html = '<div class="position-absolute w-100 h-100 div-file" style="top: 0; left: 0;z-index: 10">' +
                        '<button type="button" class="position-absolute clear border-0 bg-danger p-0 d-flex justify-content-center align-items-center" style="top: -10px;right: -10px;width: 30px;height: 30px;border-radius: 50%"><i class="bi bi-x-lg text-white"></i></button>'+
                        '<img src="'+e.target.result+'" class="w-100 h-100" style="object-fit: cover">' +
                        '</div>';
                    parent.html(html);
                }
                reader.readAsDataURL(input.files[0]);
            }else if(filetype == "video" || filetype == "mp4"){
                let html = '<div class="position-absolute w-100 h-100 div-file" style="top: 0; left: 0;z-index: 10">' +
                    '<button type="button" class="position-absolute clear border-0 bg-danger p-0 d-flex justify-content-center align-items-center" style="top: -10px;right: -10px;width: 30px;height: 30px;border-radius: 50%;z-index: 14"><i class="bi bi-x-lg text-white"></i></button>'+
                    '<video class="w-100 h-100" style="object-fit: cover" controls>\n' +
                    '<source src="'+URL.createObjectURL(input.files[0])+'"></video>'+
                    '</div>';
                parent.html(html);
            }else{
                alert("Invalid file type");
            }
        }
        $(document).on("click", "button.clear", function () {
            parent = $(this).closest(".div-parent");
            $(".div-file").remove();
            let html = '<button type="button" class="position-absolute border-0 bg-transparent select-image" style="top: 50%;left: 50%;transform: translate(-50%,-50%)">\n' +
                '                                    <i style="font-size: 30px" class="bi bi-download"></i>\n' +
                '                                </button>';
            parent.html(html);
            $('input[type="file"]').val("");
        });
    </script>
@endsection
