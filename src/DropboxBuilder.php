<?php  
namespace Andihoerudin\Dropbox;


interface DropboxBuilder {
      
    public function getToken() : mixed;

    public function refreshToken() : mixed;

    public function upload($mode = 'add', $autorename = false) : array;
    
    public function published() : mixed;

    public function listFile() : mixed;

    public function sharelink() : mixed;

    public function deleteFile() : mixed;
}

?>