<?php

namespace App\Http\Controllers;

use App\Services\FileManagerService;
use Illuminate\Http\Request;

class FileManagerController extends Controller
{
    public function __construct( protected FileManagerService $fileManagerService){}

    public function getData()
    {
        return $this->fileManagerService->get();
    }

    public function getFiles($folderId)
    {
        return $this->fileManagerService->getData($folderId);
    }

    public function create($folderId, Request $request)
    {
        return $this->fileManagerService->upload($folderId, $request);
    }

    public function download($id)
    {
        return $this->fileManagerService->download($id);
    }

    public function delete($id)
    {
        return $this->fileManagerService->delete($id);
    }

    public function search(Request $request)
    {
        return $this->fileManagerService->search($request);
    }

    public function uploadMulti($folderId, Request $request)
    {
        return $this->fileManagerService->uploadMulti($folderId, $request);
    }


}
