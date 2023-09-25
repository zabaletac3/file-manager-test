<?php

namespace App\Services;

use App\Models\File;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class FolderManagerService
{

    protected $disk;

    public function __construct($disk = 'public')
    {
        $this->disk = $disk;
    }

    public function setDisk($disk)
    {
        $this->disk = $disk;
    }

    public function getFolder()
    {
//        $folders = Folder::with('children','files')->get();
//
//        foreach ($folders as $folder) {
//            // Para cada carpeta principal, obtenemos sus carpetas secundarias
//            foreach ($folder->children as $child) {
//                // Para cada carpeta secundaria, obtenemos sus archivos
//                $child->load('files');
//            }
//
//        return $folder;
//        }

        $folders = Folder::with('children', 'children.files')->withCount('children as file_count')->get();

        return $folders;

    }


    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:folders'
        ]);

        $folder = new Folder($request->all());

//        Storage::createDirectory('public/uploads/'.$folder->name);
        Storage::disk($this->disk)->makeDirectory('uploads/'.$folder->name);

        $folder->save();

        return $folder;

    }

    public function createFolderInFolder($folderId, Request $request)
    {
        $request->validate([
            'name' => 'required|unique:folders'
        ]);

        $mainFolder = Folder::with('children')->findOrFail($folderId);

        $subFolder = new Folder($request->all());

        $subFolder->parent()->associate($mainFolder);

        $folderPath = "uploads/";

        for($i = 0; $i > count($mainFolder); $i++){
            $folderPath
        }

        if ($mainFolder->parent) {
            $folderPath = "uploads/{$mainFolder->parent->name}/{$mainFolder->name}/{$subFolder->name}";
        }

        Storage::disk($this->disk)->makeDirectory($folderPath);

        $subFolder->save();

        return $subFolder;

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
        $files = File::with('folder')->where('folder_id', $folderId)->get();
        foreach ($files as $file){
            $folderName = $file->folder->name;

            //unlink(storage_path("app/public/uploads/$folderName/". $file->name_generate));

            Storage::disk($this->disk)->deleteDirectory("uploads/$folderName");

        }

        Folder::where('id', $folderId)->delete();

        File::where('folder_id', $folderId)->delete();


        return response()->json(['success' => true, 'message' => "Folder eliminado"]);
    }



}
