<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use Illuminate\Http\Request; 
use Illuminate\Http\Response;

class Collect extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'collect';

    /**
     * The base url (domain name) for StadiumGoods 
     *
     * @var string
     */
    protected $url = 'https://www.stadiumgoods.com/';
   
    /**
     * The path url for StadiumGoods
     *
     * @var string
     */
    protected $pathUrl = '';
    
    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Collect data from given url';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        echo "base url: https://www.stadiumgoods.com/";
        $this->createURL();
        $this->getDatafromURL();
    }
    
    protected function createURL()
    {
        $this->pathUrl=$this->ask("Please provide the path after the base url");
        $this->url=$this->url.$this->pathUrl;
    }

 
    protected function  getDatafromURL(){
        $dom = new \DOMDocument();
        
        $dir="productsdata/".$this->pathUrl;
        if( is_dir($dir) === false )
          {
              mkdir($dir);
          }

        
        $productsCSV=fopen($dir."/products.csv","w",true) or die("Could not create file, Please check your permisions");
        $nextPage=false; // has next page in the pagination  
           
        $page=""; // page  page in the pagination 
        $currentPage=1; //the initial page in the pagination 
          do  
         {
            if($currentPage==1) 	 
                $ch = curl_init($this->url);
              else
                $ch = curl_init($this->url."/page/".strval($currentPage));  
         
         
         
         $fp=fopen($dir."/page".strval($currentPage), "w");
         curl_setopt($ch, CURLOPT_FILE, $fp);
         curl_setopt($ch, CURLOPT_HEADER, 0);
         if(!curl_exec($ch))
         {
              exit("Invalid URL");
         }
         curl_close($ch);
         fclose($fp); 
       
         $html=file_get_contents($dir."/page".strval($currentPage));
         libxml_use_internal_errors(true); //command used to ommit html 5 warnings
         $dom->loadHTML($html);
         $xpath = new \DOMXPath($dom);
         libxml_clear_errors();  //command used to ommit html 5 warnings
         
         
         $productNames = $xpath->query("(//*[contains(@class, 'product-name')])"); //get product name
            if($productNames->length==0) {
                unlink($dir."/page".strval($currentPage)); //remove file 
                fclose($productsCSV);
                unlink($dir."/products.csv");   //remove CSV file 
                rmdir($dir);     //remove directory
                exit("Please provide valid path");
            
            }
         
         $productPrice = $xpath->query("(//*[contains(@class, 'price-box')])");     //get product price
         $paginator= $xpath->query("(//*[contains(@class, 'sg-pager has-pagination')])");  //get paginatior
         $pagination=preg_replace('/\s+/', ' ',preg_replace("/[\n\r]/","",$paginator->item(0)->nodeValue)); //get pagination element as NodeList
           
           if (strpos($pagination , strval($currentPage+1)) !== false) {  //check if contains next page in the pagination 
                 $currentPage++; 
                 $nextPage=true;
                 $page=strval($currentPage);
             }
             else
             {
                 $nextPage=false;
             }
           
     
         for($nodeCounter=0;$nodeCounter<($productNames->length);$nodeCounter++)
         {  
             $productData=ltrim(preg_replace('/\s+/', ' ',$productNames->item($nodeCounter)->nodeValue)).",".trim(preg_replace('/\s+/', ' ',$productPrice->item($nodeCounter)->nodeValue)); 
             fputcsv($productsCSV, explode(",",$productData));  	//write product data on the CSV file 
             echo $productData. "\r\n";
         }

         
        }while($nextPage);//end do-while  has next page	
         
         
         fclose($productsCSV); //close the file 
         
         
         
           for($i=1;$i<=$currentPage;$i++)
              unlink($dir."/page".strval($i));
         
        echo "your products from ".$this->url." are listed on ".getcwd().$dir."/products.csv"; 

    }

}
