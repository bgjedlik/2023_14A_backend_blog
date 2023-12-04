<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use App\Http\Resources\Blog as ResourceBlog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BlogController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $blogs = Blog::with('user')->get();
        return $this->sendResponse(ResourceBlog::collection($blogs),'Bejegyzések elküldve.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $input = $request->all();

        $validator = Validator::make($input,[
            'title' => 'required',
            'description' => 'required'
        ]);

        if ($validator->fails()){
            return $this->sendError($validator->errors(),[],400);
        }

        $input['user_id'] = $user->id;

        $blog = Blog::create($input);
        return $this->sendResponse(new ResourceBlog($blog),'Bejegyzés létrehozva.' );
    }

    /**
     * Display the specified resource.
     */
    public function show(Blog $blog)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Blog $blog)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Blog $blog)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Blog $blog)
    {
        //
    }
}
