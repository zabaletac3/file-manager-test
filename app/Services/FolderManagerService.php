<?php

namespace App\Services;

use App\Models\File;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class FolderManagerService
{

    protected $disk;

    public function __construct($disk = 'google')
    {
        $this->disk = $disk;
    }

    public function setDisk($disk)
    {
        $this->disk = $disk;
    }

    public function getFolder()
    {

        $rootFolders = Folder::whereNull('parent_id')->with('files','children.files')->get();

        foreach ($rootFolders as $rootFolder) {
            $this->getAllChildren($rootFolder);
        }

        return $rootFolders;
    }

    public function getFolderOne($id)
    {
        $folder = Folder::with('files', 'children.files')->findOrFail($id);

        $this->getAllChildren($folder);

        return $folder;
    }

    public function getAllChildren($folder): void
    {
        //$folder->load('children.files');
        foreach ($folder->children as $child) {
            $this->getAllChildren($child);
        }
    }



    public function create(Request $request): Folder
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

    public function createFolderInFolder($folderId, Request $request): Folder
    {
        $request->validate([
            'name' => 'required|unique:folders'
        ]);

        $mainFolder = Folder::with('parent')->findOrFail($folderId);

        $subFolder = new Folder($request->all());

        $folderPath = "uploads/";

        // Agrega el nombre del folder principal al inicio de la ruta
        //$folderPath .= $mainFolder->name . '/';

        if ($mainFolder->parent) {
            // Recorre los folders padres para construir la ruta
            $this->buildFolderPath($mainFolder->parent, $folderPath);
        }

        // Agrega el nombre del folder principal al inicio de la ruta
        $folderPath .= $mainFolder->name . '/';

        // Agrega el nombre de la nueva carpeta al final de la ruta
        $folderPath .= $subFolder->name;

        // Crea el directorio utilizando la ruta completa
        Storage::disk($this->disk)->makeDirectory($folderPath);

        $subFolder->parent()->associate($mainFolder);

        $subFolder->save();

        return $subFolder;
    }

    private function buildFolderPath($folder, &$folderPath): void
    {
        if ($folder->parent) {
            $this->buildFolderPath($folder->parent, $folderPath);
        }
        $folderPath .= $folder->name . '/';
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
        $folder = Folder::findOrFail($folderId);

        // Construir la ruta completa de eliminación
        $directoryPath = "uploads/";

        // Obtener todos los padres de la carpeta
        $parents = [];
        $currentFolder = $folder;

        while ($currentFolder) {
            array_unshift($parents, $currentFolder);
            $currentFolder = $currentFolder->parent;
        }

        // Construir la ruta de eliminación recorriendo los padres
        foreach ($parents as $parent) {
            $directoryPath .= "{$parent->name}/";
        }

        //dd($directoryPath);

        // Eliminar el directorio y su contenido
        Storage::disk($this->disk)->deleteDirectory($directoryPath);

        // Eliminar la carpeta de la base de datos
        $folder->delete();

        return response()->json(['success' => true, 'message' => "Folder eliminado"]);
    }




    public function delete1($folderId): \Illuminate\Http\JsonResponse
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
