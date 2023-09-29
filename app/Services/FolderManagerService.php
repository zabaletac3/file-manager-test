<?php

namespace App\Services;

use App\Models\File;
use App\Models\Folder;
use App\Providers\GoogleDriveServiceProvider;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;


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

        try {


            $rootFolders = Folder::whereNull('parent_id')->with('files', 'children.files')->get();

            foreach ($rootFolders as $rootFolder) {
                $this->getAllChildren($rootFolder);
            }

            return $rootFolders;


        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'errors' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getFolderOne($id)
    {

        try {

            $folder = Folder::with('files', 'children.files')->findOrFail($id);

            //dd($folder);

            $this->getAllChildren($folder);

            return $folder;

            //return Storage::disk($this->disk)->files('uploads/test Google/archivos1');

        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'errors' => 'Carpeta no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'errors' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function getAllChildren($folder): void
    {
        //$folder->load('children.files');
        foreach ($folder->children as $child) {
            $this->getAllChildren($child);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \League\Flysystem\FilesystemException
     */
    public function create11111111(Request $request)
    {

        try {

            $parentFolderId = $request->input('parent_folder_id'); // ID de la carpeta padre opcional

            // Validar las reglas de validación según tus necesidades
            $request->validate([
                'name' => 'required|unique:folders',
                'parent_folder_id' => 'nullable|exists:folders,id', // Validar que el ID de la carpeta padre existe si se proporciona
            ]);

            $folder = new Folder($request->only(['name']));

            // Crear la carpeta en la ubicación deseada
            if ($parentFolderId) {
                // Si se proporciona un ID de carpeta padre, crear una subcarpeta
                $parentFolder = Folder::findOrFail($parentFolderId); // Asegúrate de que la carpeta padre exista
                $subFolderPath = 'uploads/' . $parentFolder->name . '/' . $folder->name;
                Storage::cloud()->makeDirectory($subFolderPath);

                // Obtener información de la subcarpeta recién creada
                $dir = 'uploads/' . $parentFolder->name;
            } else {
                // Si no se proporciona un ID de carpeta padre, crear una carpeta en la raíz
                $folderPath = 'uploads/' . $folder->name;
                Storage::cloud()->makeDirectory($folderPath);

                // Obtener información de la carpeta recién creada en la raíz
                $dir = 'uploads';
            }

            //dd($dir);

            // Guardar la información de la carpeta en la base de datos
            //$folder->save();

            //return $dir.'/'.$folder->name;

            // Obtener la información de la carpeta recién creada
            $recursive = true;
            $contents = collect(Storage::cloud()->listContents($dir, $recursive));
            $path = $contents->where('type', 'dir')->where('path', $dir.'/'.$folder->name)->first();

            return response()->json([
                'success' => true,
                'message' => 'Carpeta creada correctamente',
                'data' => $folder,
                'path' => $path,
            ], Response::HTTP_CREATED);

        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'errors' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(Request $request)
    {

        try {

        $request->validate([
            'name' => 'required|unique:folders'
        ]);

        $folder = new Folder($request->all());

        //Storage::disk($this->disk)->makeDirectory('uploads/' . $folder->name)
        Storage::cloud()->makeDirectory('/uploads/' . $folder->name);

        $folder->save();

        return response()->json(['success' => true, 'message' => 'Folder creado correctamente', 'data' => $folder], Response::HTTP_CREATED);

        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'errors' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createFolderInFolder($folderId, Request $request)
    {

        try {

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


        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'errors' => 'Carpeta no encontrada.'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'errors' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
        foreach ($files as $file) {
            $folderName = $file->folder->name;

            //unlink(storage_path("app/public/uploads/$folderName/". $file->name_generate));

            Storage::disk($this->disk)->deleteDirectory("uploads/$folderName");

        }


        Folder::where('id', $folderId)->delete();

        File::where('folder_id', $folderId)->delete();


        return response()->json(['success' => true, 'message' => "Folder eliminado"]);
    }


}
