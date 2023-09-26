<?php

namespace App\Http\Controllers;

use App\Services\FileManagerService;
use Illuminate\Http\Request;

class FileManagerController extends Controller
{
    public function __construct( protected FileManagerService $fileManagerService){}

    public function getAll()
    {
        return $this->fileManagerService->findAll();
    }

    public function getById(int $id)
    {
        return $this->fileManagerService->findById($id);
    }

    public function getByFolderId(int $folderId)
    {
        return $this->fileManagerService->findByFolderId($folderId);
    }

    public function upload(int $folderId, Request $request)
    {
        return $this->fileManagerService->uploadMulti($folderId, $request);
    }

    public function download(int $id)
    {
        return $this->fileManagerService->download($id);
    }

    public function delete(int $id)
    {
        return $this->fileManagerService->delete($id);
    }

    public function search(Request $request)
    {
        return $this->fileManagerService->search($request);
    }



}
