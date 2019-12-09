<?php
namespace App\Repositories;

use App\Models\Product;
use Carbon\Carbon;

class ProductRepository extends BaseRepository
{
    /**
     * @var string
     */
    public $modelName = Product::class;

    /**
     * @param $type
     * @return mixed
     */
    public function simTypeProduct($type)
    {
        return $this->model->with('productCore')->where('status', 1)
            ->startEndDate()
            ->category($type)
            ->get();
    }

    public function showTrandingProduct()
    {
       return $this->model->where('show_in_home', 1)
            ->with('productCore')
            ->where('status', 1)
            ->startEndDate()
            ->orderBy('display_order')
            ->get();
    }

    /**
     * @param $type
     * @param $id
     * @return mixed
     */
    public function detailProducts($type, $id)
    {
        return $this->model->where('id', $id)
            ->category($type)
            ->with('product_details', 'related_product', 'other_related_product')
            ->first();
    }

}
