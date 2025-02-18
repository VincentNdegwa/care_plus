<?php

namespace App\Http\Controllers\Uploads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,txt|max:2048',
            'folder' => 'required|string'
        ]);

        try {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $folder = $request->folder;
    
            $filePath = $file->storeAs($folder, $fileName, 'public');
    
            return response()->json([
                "error" => false,
                "data"=>[
                    'file_path' => Storage::url($filePath),
                    "url"=> env('APP_URL').Storage::url($filePath),
                    "file_name"=>$fileName,
                    "folder"=>$folder
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "error" => true,
                "message" => $th->getMessage()
            ],500);
        }
    }

    public function delete(Request $request)
    {
        $request->validate([
            'file_path' => 'required|string',
        ]);

        $file_path = $request->file_path;

        if(Storage::disk('public')->exists($file_path)){
            Storage::disk('public')->delete($file_path);
            return response()->json([
                "error" => false,
                "message" => "File deleted successfully"
            ]);
        }else{
            return response()->json([
                "error" => true,
                "message" => "File not found"
            ],404);
        }
    }
}
