<?php  
namespace Andihoerudin\Dropbox;


class Dropboxendpoint {
    
    /* 
      url end point auth
    */
    const REFRESHTOKEN = 'oauth2/token';
    
    /* 
      api url
    */
    const API_URL      = 'https://api.dropbox.com';

    /* 
      base url
    */
    const BASE_URL      = 'https://api.dropboxapi.com';
    
    /* 
      content url upload
    */
    const UPLOAD_URL      = 'https://content.dropboxapi.com';
    
    /* 
      url end point upload
    */
    const END_POINT_UPLOAD_URL      = '/2/files/upload';

    /* 
      url end point publish file
    */
    const END_POINT_SHARE_LINK_FILE      = '/2/sharing/create_shared_link_with_settings';
    
    /* 
      url end point lisf file of folder
    */
    const END_POINT_LIST_FILE_OF_FOLDER     = '/2/files/list_folder';
   
    
    /* 
      url end point delete file of folder
    */
    const END_POINT_DELETE_FILE_OF_FOLDER     = '/2/files/delete_v2';
    

}

?>