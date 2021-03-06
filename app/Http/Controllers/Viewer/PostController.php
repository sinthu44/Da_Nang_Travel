<?php

namespace App\Http\Controllers\Viewer;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use App\Http\Controllers\BaseController;

use App\Presenters\PostPresenter;
use App\Http\Requests\SearchRequest;
use App\Repositories\Contracts\PostRepository;
use App\Criteria\Post\FilterByPostActiveCriteria;
use App\Repositories\Contracts\CategoryRepository;
use App\Criteria\Category\FilterByCategoryActiveCriteria;

class PostController extends BaseController
{
    /**
     * $post, $category repository
     * @var repository
     */
    protected $post;
    protected $category;

    /**
     * $paginate hiển thị số lượng bài viết
     * @var int
     */
    protected $paginate = 15;

    public function __construct(
        PostRepository $post,
        CategoryRepository $category
    ){
        $this->category = $category;
        $this->post     = $post;

        $this->post->setPresenter(PostPresenter::class);
        $this->post->pushCriteria(FilterByPostActiveCriteria::class);
        $this->category->skipPresenter();
        $this->category->pushCriteria(FilterByCategoryActiveCriteria::class);
    }

    /**
     * hiển thị tất cả bài viết từ mới đến cũ
     *
     * @param  string  $uri_category đây là thông tin unique của danh mục để lọc ra bài viết
     * @return Illuminate\Http\Response
     */
    public function index(Request $request, $uri_category)
    {
        $category = $this->category->findByUri($uri_category);
        $posts    = $this->post
            ->with(['category', 'tags'])
            ->filterByCategory($category->id)
            ->latest()
            ->paginate($this->paginate);
        return $this->responses(trans('notication.load.success'), Response::HTTP_OK, $posts);
    }

/**
     * Hiển thị thông tin chi tiết của bài viết
     *
     * @param  string  $uri_category đây là thông tin unique của danh mục để lọc ra bài viết
     * @param  string  uri_post     đây là thông tin unique của bài viết để show bài viết
     * @return Illuminate\Http\Response
     */
    public function show(Request $request, $uri_category, $uri_post)
    {
        $post     = $this->post->with(['category', 'tags'])->findByUri($uri_post);
        $category = $this->category->findByUri($uri_category);

        $relationPost = $this->post
            ->with(['category', 'tags'])
            ->filterByRelationPost($uri_post)
            ->findByField('category_id', $category->id);
        Event::fire('posts.view', $post['data']['id']);

        return response()->json(compact('post','relationPost'), Response::HTTP_OK);
    }

/**
 * Chức năng dùng để search bài viết ở mọi niw
 *
 * @return Illuminate\Http\Response
 */
    public function search(Request $request)
    {
        $posts = $this->post
            ->with(['category', 'tags'])
            ->paginate($this->paginate);

        return $this->responses(trans('notication.load.success'), Response::HTTP_OK, $posts);
    }
}
