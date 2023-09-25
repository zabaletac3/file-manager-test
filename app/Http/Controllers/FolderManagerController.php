<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Folder;
use App\Services\FolderManagerService;
use Illuminate\Http\Request;

class FolderManagerController extends Controller
{
    public function __construct( protected FolderManagerService $folderManagerService){}

    public function create(Request $request)
    {
        return $this->folderManagerService->create($request);
    }

    public function edit(Request $request, $id)
    {
        return $this->folderManagerService->edit($request, $id);
    }

    public function delete($folderId)
    {
        return $this->folderManagerService->delete($folderId);
    }


}


