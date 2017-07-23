<?php
 if(isset($_FILES['upload'])){
  // ------ Process your file upload code -------

       /*
        $filen = $_FILES['upload']['tmp_name'];
        $con_images = "img/uploaded/".$_FILES['upload']['name'];
        move_uploaded_file($filen, $con_images );
       $url = "http://www.limetal.cz/".$con_images;*/

 $soubor = ($_FILES["upload"]["tmp_name"]);
$soubor_name = ($_FILES["upload"]["name"]);
$soubor_size = ($_FILES["upload"]["size"]/1024)/1024;
$pripona = strtolower(pathinfo($soubor_name, PATHINFO_EXTENSION));
move_uploaded_file ($soubor, "uploaded/".$soubor_name);
$url = "http://www.limetal.cz/lib/ckeditor/plugins/uploadimage/uploaded/".$soubor_name;

   $funcNum = $_GET['CKEditorFuncNum'] ;
   // Optional: instance name (might be used to load a specific configuration file or anything else).
   $CKEditor = $_GET['CKEditor'] ;
   // Optional: might be used to provide localized messages.
   $langCode = $_GET['langCode'] ;

   // Usually you will only assign something here if the file could not be uploaded.
   $message = '';
   echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($funcNum, '$url', '$message');</script>";
}
?>