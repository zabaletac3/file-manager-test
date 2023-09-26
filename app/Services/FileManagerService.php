<?php

namespace App\Services;

use App\Models\File;
use App\Models\Folder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class FileManagerService
{

    protected $disk;
    public function __construct(
        protected Folder $folder,
        protected File $file,
        $disk = 'public'
    ){
        $this->disk = $disk;
    }


    public function setDisk($disk)
    {
        $this->disk = $disk;
    }

    public function findAll()
    {
        try {

            $files = File::orderBy('id', 'desc')->get();

            return response()->json(['success' => true, 'data' => $files ], Response::HTTP_OK);

        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'errors' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findById(int $id)
    {
        try {

            $file = File::findOrFail($id);

            return response()->json(['success' => true, 'data' => $file], Response::HTTP_OK);

        } catch (ModelNotFoundException){
            return response()->json(['success' => false, 'errors' => 'Archivo no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'errors' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Obtener archivos de una carpeta
    public function findByFolderId(int $folderId)
    {
        try {

            $files = File::where('folder_id', $folderId)->get();
            //$formattedSize = $this->formatSize($files->first()->size);

            if ($files->isEmpty()) {
                return response()->json(['success' => true, 'errors' => 'Archivos no encontrados.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['success' => false, 'data' => $files], Response::HTTP_OK);

        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'errors' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function uploadMulti(int $folderId, Request $request)
    {
        try {

        $request->validate([
            'files.*' => 'required|mimes:png,jpg,pdf,html,docx,xlsx|max:2048',
        ]);

        // Obtener la carpeta principal
        $mainFolder = Folder::findOrFail($folderId);

        $folderPath = $this->buildFilePath($mainFolder);

        // Crear la carpeta si no existe
        // Storage::disk($this->disk)->makeDirectory($folderPath);

        foreach ($request->files as $fileGroup) {
            foreach ($fileGroup as $item) {
                if ($item->isValid()) {
                    $name = $item->getClientOriginalName();
                    $fileName = time() . '_' . $item->getClientOriginalName();
                    $type = $item->getClientOriginalExtension();
                    $size = $item->getSize();

                    // Guardar el contenido real del archivo en la ruta completa
                    Storage::put("{$folderPath}/{$fileName}", file_get_contents($item->getRealPath()));

                    $fileModel = new File;
                    $fileModel->name = $name;
                    $fileModel->name_generate = $fileName;
                    $fileModel->type = $type;
                    $fileModel->size = $size;
                    $fileModel->folder_id = $folderId;
                    $fileModel->save();
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Los archivos se han cargado correctamente'], Response::HTTP_CREATED);

        } catch (ModelNotFoundException){
            return response()->json(['success' => false, 'errors' => 'Carpeta no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'errors' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function download(int $id)
    {
        try {

            $file = File::findOrFail($id);
            $folder = $file->folder;

            $relativeFilePath = $this->buildFilePath($folder, 'uploads') . "/{$file->name_generate}";

            if(Storage::disk($this->disk)->exists($relativeFilePath)){
                return Storage::disk($this->disk)->download($relativeFilePath);
            } else {
                return response()->json(['success' => false, 'errors' => 'Archivo no encontrado.'], Response::HTTP_NOT_FOUND);
            }

        } catch (ModelNotFoundException){
            return response()->json(['success' => false, 'errors' => 'Archivo no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'errors' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    public function delete(int $id)
    {

        try {

        $file = File::findOrFail($id);
        $folder = $file->folder;

        $relativeFilePath = $this->buildFilePath($folder, 'uploads') . "/{$file->name_generate}";

        // Utilizar la función para eliminar el archivo de la carpeta public
        if(Storage::disk($this->disk)->exists($relativeFilePath)) {
            Storage::disk($this->disk)->delete($relativeFilePath);
            return response()->json(['success' => true, 'message' => 'El archivo se ha eliminado correctamente'], Response::HTTP_OK);
        } else {
            return response()->json(['success' => false, 'errors' => 'Archivo no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        } catch (ModelNotFoundException){
            return response()->json(['success' => false, 'errors' => 'Archivo no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'errors' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function search(Request $request)
    {
        try {

            $request->validate([
                'keyword' => 'required|string|max:255', // Agregar reglas de validación según tus requisitos
            ]);

            $files = File::where('name', 'like', '%'.$request->keyword.'%')->get();

            return response()->json(['success' => true, 'data' => $files], Response::HTTP_OK);

        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'errors' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function formatSize($bytes)
    {
        $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $i = ($bytes != 0) ? floor(log($bytes, 1024)) : 0;
        return round($bytes / pow(1024, $i), 2) . ' ' . $sizes[$i];
    }

    private function buildFilePath($folder, $basePath = "public/uploads")
    {
        // Construir la ruta completa de eliminación
        $directoryPath = $basePath;

        // Obtener todos los padres de la carpeta
        $parents = [];
        $currentFolder = $folder;

        while ($currentFolder) {
            array_unshift($parents, $currentFolder);
            $currentFolder = $currentFolder->parent;
        }

        // Construir la ruta de eliminación recorriendo los padres
        foreach ($parents as $parent) {
            $directoryPath .= "/{$parent->name}";
        }

        return $directoryPath;
    }

}
