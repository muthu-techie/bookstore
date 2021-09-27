<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\BookCategory;
use App\Models\Author;
use App\Models\Book;
use Illuminate\Http\Request;
use DataTables;
use Flash;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Book::orderBy('id','DESC');
            return Datatables::of($query)
            ->addColumn('author_name', function ($query) {
                return $query->author->name ?? null;
            })->addColumn('book_category', function ($query) {
                return $query->category->name ?? null;
            })->addColumn('image', function ($query) {
                $media_name = $query->media ?? null;
                $img_url = null;
                if ($media_name) {
                    $img_url = storage_url(Book::BOOK_COVER_PATH . $media_name);
                }
                return '<a href="'.$img_url.'" target=”_blank”>
                <img src="'.$img_url.'" alt="book_cover" width="25px" height="25px"/>
                </a>';
            })->addColumn('action', function ($query) {
                return view('admin.partials.action', [
                'item' => $query,
                'source' => 'books',
                'view_btn' => true,
                'edit_btn' => true,
                'delete_btn' => true,
                ]);
            })->rawColumns(['action', 'image'])->make(true);
        }
        return view('admin.books.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $bookCategory = BookCategory::get();
        $authors = Author::get();
        $in_stock = Book::getStockStatus();
        return view('admin.books.create', compact('bookCategory', 'authors', 'in_stock'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = $this->validator($request->all());
        if ($validator->fails()) {
            return redirect()->back()->withInput($request->all())->withErrors($validator->errors());
        }
        $book_data = [
            'name' => $request->book_name,
            'author_id' => $request->author_name,
            'category_id' => $request->book_category,
            'price' => $request->price,
            'pages' => $request->total_pages,
            'publication' => $request->publication,
            'edition' => $request->edition,
            'in_stock' => $request->in_stock,
        ];
        $create_book = Book::create($book_data);
        if ($img_file = $request->file('media')) {
            $image_name = getRandomFileName($img_file);
            Book::uploadImage($img_file, $image_name);
            $create_book->media = $image_name;
        }
        $create_book->save();
        Flash::success(trans('messages.book_create'));
        return redirect()->route('admin.books.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Book  $book
     * @return \Illuminate\Http\Response
     */
    public function show(Book $book)
    {
        if ($book) {
            return view('admin.books.show', compact('book'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Book  $book
     * @return \Illuminate\Http\Response
     */
    public function edit(Book $book)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Book  $book
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Book $book)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Book  $book
     * @return \Illuminate\Http\Response
     */
    public function destroy(Book $book)
    {
        //
    }

    protected function validator(array $data)
    {
        $rules = [
            'book_name'       => 'required',
            'author_name'     => 'required',
            'book_category'   => 'required',
            'media'           => 'required|image|mimes:jpeg,png,jpg,svg',
            'price'           => 'required',
            'total_pages'     => 'required',
            'publication'     => 'required',
            'edition'         => 'required',
            'in_stock'        => 'required',
        ];

        return Validator::make($data, $rules);
    }
}
