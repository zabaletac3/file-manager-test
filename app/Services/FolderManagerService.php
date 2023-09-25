<?php

namespace App\Services;

use App\Models\File;
use App\Models\Folder;
use Illuminate\Http\Request;

class FolderManagerService
{

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $folder = Folder::create($request->all());

        return $folder;

    }

    public function edit(Request $request, $id)
    {
        $request->validate([
            'name' => 'required'
        ]);

        $folder = Folder::where('id', $id)->update($request->all());

        return $folder;

    }

    public function delete($folderId)
    {
        $files = File::where('folder_id', $folderId)->get();
        foreach ($files as $file){
            unlink(storage_path('app/public/uploads/'. $file->name_generate));
        }
        $folder = Folder::where('id', $folderId)->delete();
        $files  = File::where('folder_id', $folderId)->delete();
        return $folder;
    }



}
