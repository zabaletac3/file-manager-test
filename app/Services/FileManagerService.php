<?php

namespace App\Services;

use App\Models\File;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileManagerService
{

    public function __construct(
        protected Folder $folder,
        protected File $file,
    ){}

    public function get()
    {
        $folders = Folder::all();
        $files = File::orderBy('id', 'desc')->get();

        return [
            'folders' => $folders,
            'files' => $files,
        ];

    }

    // Obtener archivos de una carpeta
    public function getData(int $folderId)
    {
        $files = File::where('folder_id', $folderId)->get();
        $formattedSize = $this->formatSize($files->first()->size);
        return [$files, $formattedSize];
    }

    public function upload($folderId, Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:png,jpg,pdf,html,docx|max:2048'
        ]);

        $folder = Folder::where('id', $folderId)->first();

        $file = $request->file('file');


        $fileName = time() . '_' . $file->getClientOriginalName();

        $file->storeAs('uploads/'.$folder->name, $fileName, 'public');

        $data = new File([
            'name' => $file->getClientOriginalName(),
            'name_generate' => $fileName,
            'type' => $file->guessExtension(),
            'size' => $file->getSize(),
            'folder_id' => $folderId
        ]);

        $data->save();

        return  $data;

    }

    public function download($id)
    {
        $file = File::where('id', $id)->first();
        $path = storage_path('app/public/uploads/'. $file->name_generate);
        return response()->download($path);
    }

    public function delete($id)
    {
        $file = File::where('id', $id)->first();

        unlink(storage_path('app/public/uploads/'. $file->name_generate));

        $result = File::where('id', $id)->delete();

        return $result;

    }

    public function search(Request $request)
    {
        $files = File::where('name', 'like', '%'.$request->keyword.'%')->get();

        return $files;
    }

    public function uploadMulti($folderId, Request $request)
    {
        $request->validate([
            'files.*' => 'required|mimes:png,jpg,pdf,html,docx,xlsx|max:2048',
        ]);


        foreach ($request->files as $file) {

            foreach ($file as $item){
                $name = $item->getClientOriginalName();
                $fileName = time().'_'.$item->getClientOriginalName();
                $type = $item->getClientOriginalExtension();
                $size = $item->getSize();

                $folder = Folder::findOrFail($folderId);

                Storage::put("public/uploads/{$folder->name}/{$fileName}", $fileName);

                $fileModel = new File;

                $fileModel->name = $name;
                $fileModel->name_generate = $fileName;
                $fileModel->type = $type;
                $fileModel->size = $size;
                $fileModel->folder_id = $folderId;

                $fileModel->save();
            }
        }
        return response()->json(['success File has successfully uploaded']);
    }

    public function formatSize($bytes)
    {
        $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $i = ($bytes != 0) ? floor(log($bytes, 1024)) : 0;
        return round($bytes / pow(1024, $i), 2) . ' ' . $sizes[$i];
    }

}
