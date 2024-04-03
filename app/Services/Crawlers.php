<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class Crawlers
{
    protected $domain = "https://vn.toto.com/";

    protected $key_fill_crawler = [
        'title' => '.product-name .product-title',
        'meta_title' => 'title',
        'meta_description' => 'meta[name="description"]',
        'meta_keyword' => 'meta[name="keywords"]',
        'description' => ".short-description",
        'code' => '.product-info-content .info-value',
        'size' => '.product-info-content .info-value',
        'content' => ".product-content-left .block-content",
        'price' => '.price-info .price',
        'image' => ".product-image-gallery img#image-main",
        'product_specifications' => ".product-specifications .block-content",
        'product_features' => ".product-features .block-content",
        'product_attachments' => ".product-attachments li",
    ];


    public function crawler_detail_product($url)
    {
        $crawler_url = $this->curl_html($url);
        $crawls = new Crawler($crawler_url);
        $data['meta_title'] = $crawls->filter($this->key_fill_crawler['meta_title'])->text();
        $data['meta_keyword'] = $crawls->filter($this->key_fill_crawler['meta_keyword'])->attr('content');
        $data['meta_description'] = $crawls->filter($this->key_fill_crawler['meta_description'])->attr('content');
        $data['title'] = $crawls->filter($this->key_fill_crawler['title'])->text();
        $data['slug'] = $slug = Str::slug($data['title']);
        $thubnail = $crawls->filter($this->key_fill_crawler['image'])->attr('src');
        if (!empty($thubnail)) {
            $this->grab_image($thubnail,  public_path("/storage/$slug.jpg"), 'https://vn.toto.com');
            $data['image'] = "/storage/$slug.jpg";
        }

        $data['description'] = $crawls->filter($this->key_fill_crawler['description'])->html();
        $data['code'] = $crawls->filter($this->key_fill_crawler['code'])->count() > 0 ? $crawls->filter($this->key_fill_crawler['code'])->eq(0)->html() : '';
        $data['size'] = $crawls->filter($this->key_fill_crawler['size'])->count() > 1 ? $crawls->filter($this->key_fill_crawler['size'])->eq(1)->html() : '';
        $data['price'] = str_replace(['.', ' ', '&nbsp;₫'], '', $crawls->filter($this->key_fill_crawler['price'])->html());
        $data['product_specifications'] = $crawls->filter($this->key_fill_crawler['product_specifications'])->html();
        $data['product_features'] = $crawls->filter($this->key_fill_crawler['product_features'])->html();
        $data['product_attachments'] = json_encode($crawls->filter($this->key_fill_crawler['product_attachments'])->each(function ($node) {
            return [
                'title' => $node->filter("a")->text(),
                'href' => $this->domain . trim($node->filter("a")->attr('href'), '/'),
            ];
        }));
        $data['content'] = $crawls->filter($this->key_fill_crawler['content'])->html();
       
        return $data;
    }


    function index($page = 1)
    {
        $url = "https://vn.toto.com/san-pham-moi?fbclid=IwAR18qjD1Ai-1VNrHJ-3AdEvqjKxOxTmkUK8rrFN5RFFtp1PWQJiaG-OBNBA_aem_AQ5ukSuJ3wlN3GIpVLlw4buv4mKZO7hXRnZ8RhgxYIbdEDMZV-qqPlZRRAmKwU86SEWtqnz61qZ-dNQ1MaKZo71x";
        $crawler_url = $this->curl_html($url);
        $crawls = new Crawler($crawler_url);
        $list_url_product = $crawls->filter('.category4-products .product-name a')->each(function ($node) {
            return [
                'title' => $node->text(),
                'crawler_href' => "{$this->domain}" . $node->attr('href')
            ];
        });
        if (!empty($list_url_product)) {
            foreach ($list_url_product as $key => $val) {
                $check = Product::where('crawler_href', $val['crawler_href'])->first();
                if (empty($check)) {
                    $data = $this->crawler_detail_product($val['crawler_href']);

                    if (empty($data)) {
                        echo ("Error! {$val['title']} not crawler\n");
                        continue;
                    }
                    try {
                        $arr_key = ['title', 'slug', 'code',  'meta_title', 'meta_description', 'meta_keyword',   'description', 'content', 'price', 'size',  'image',  'product_specifications', 'product_features', 'product_attachments',];
                        $input = Arr::only($data, $arr_key);

                        DB::beginTransaction();
                        Product::create($input);
                        DB::commit();
                        echo ("Success! {$val['title']}\n");
                    } catch (\Exception $ex) {
                        DB::rollback(); 
                        echo ("Error! {$val['title']} not crawler\n");
                    }
                } else {
                    echo ("Warning! {$val['title']} exists\n");
                }
            }
        }

        die("\nDone page $page\n");
    }

    public function grab_image($url, $saveto, $domain)
    {

        try {
            if (!is_dir(dirname($saveto))) {
                mkdir(dirname($saveto), 777, true);
            }

            $pointer = curl_init();
            curl_setopt($pointer, CURLOPT_URL, $url);
            curl_setopt($pointer, CURLOPT_TIMEOUT, 40);
            curl_setopt($pointer, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($pointer, CURLOPT_USERAGENT, "Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1");
            curl_setopt($pointer, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($pointer, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($pointer, CURLOPT_HTTPHEADER, array(
                'Referer: ' . $domain,
            ));
            curl_setopt($pointer, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($pointer, CURLOPT_AUTOREFERER, true);

            $return_val = curl_exec($pointer);
            curl_close($pointer);
            if (file_exists($saveto)) {
                unlink($saveto);
            }
            unset($pointer);
            $fp = fopen($saveto, 'x');
            fwrite($fp, $return_val);
            fclose($fp);
        } catch (\Exception $ex) {
            return '';
        }
    }

    public function curl_html($url)
    {
        $pointer = curl_init();
        curl_setopt($pointer, CURLOPT_URL, $url);
        curl_setopt($pointer, CURLOPT_TIMEOUT, 40);
        curl_setopt($pointer, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($pointer, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.10 (KHTML, like Gecko) Chrome/8.0.552.28 Safari/534.10");
        curl_setopt($pointer, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($pointer, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($pointer, CURLOPT_HEADER, false);
        curl_setopt($pointer, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($pointer, CURLOPT_AUTOREFERER, true);

        $return_val = curl_exec($pointer);

        $http_code = curl_getinfo($pointer, CURLINFO_HTTP_CODE);
        if ($http_code == 404) {
            return false;
        }
        curl_close($pointer);
        unset($pointer);
        return $return_val;
    }
}
