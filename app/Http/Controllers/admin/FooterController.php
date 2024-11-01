<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\FooterModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FooterController extends Controller
{
    public function index()
    {
        $titlePage = 'Danh sách bài viết';
        $page_menu = 'footer';
        $page_sub = null;
        $listData = FooterModel::orderBy('created_at', 'desc')->paginate(10);
        foreach ($listData as $item){
            if ($item->type == 1){
                $item->name_type = 'Hỗ trợ khách hàng';
            }elseif ($item->type == 2){
                $item->name_type = 'Về chúng tôi';
            }else{
                $item->name_type = 'Hợp tác và liên kết';
            }

        }

        return view('admin.footer.index', compact('titlePage', 'page_menu', 'page_sub', 'listData'));
    }

    public function create ()
    {
        try{
            $titlePage = 'Thêm bài viết';
            $page_menu = 'footer';
            $page_sub = null;
            return view('admin.footer.create', compact('titlePage', 'page_menu', 'page_sub'));
        }catch (\Exception $e){
            return back()->with(['error' => $e->getMessage()]);
        }
    }

    public function store (Request $request)
    {
        try{
            $data = FooterModel::where('name',$request->get('name'))->first();
            if ($data){
                toastr()->error('Bài viết đã tồn tại');
                return back();
            }

            $new = new FooterModel([
                'name' => $request->get('name'),
                'slug' => Str::slug($request->get('name')),
                'content' => $request->get('content'),
                'type' => $request->get('type'),
            ]);
            $new->save();
            return redirect()->route('admin.footer.index')->with(['success' => 'Tạo bài viết thành công']);
        }catch (\Exception $exception){
            return back()->with(['error' => $exception->getMessage()]);
        }
    }

    public function delete ($id)
    {
        $new = FooterModel::find($id);
        $new->delete();
        return redirect()->route('admin.footer.index')->with(['success'=>"Xóa dữ liệu thành công"]);
    }

    public function edit ($id)
    {
        try{
            $new = FooterModel::find($id);
            if (empty($new)) {
                return back()->with(['error' => 'Bài viết không tồn tại']);
            }
            $titlePage = 'Sửa bài viết';
            $page_menu = 'footer';
            $page_sub = null;
            return view('admin.footer.edit', compact('titlePage', 'page_menu', 'page_sub', 'new'));
        }catch (\Exception $exception){
            return back()->with(['error' => $exception->getMessage()]);
        }
    }

    public function update ($id, Request $request)
    {
        try{
            $new = FooterModel::find($id);
            if (empty($new)){
                return back()->with(['error' => 'Dữ liệu không tồn tại']);
            }
            $data = FooterModel::where('name',$request->get('name'))->first();
            if ($data && $data->id != $id){
                toastr()->error('Bài viết đã tồn tại');
                return back();
            }

            $new->name = $request->get('name');
            $new->slug = Str::slug($request->get('name'));
            $new->type = $request->get('type');
            $new->content = $request->get('content');
            $new->save();
            return redirect()->route('admin.footer.index')->with(['success' => 'Cập nhật bài viết thành công']);
        }catch (\Exception $e){
            return back()->with(['error' => $e->getMessage()]);
        }
    }
}
