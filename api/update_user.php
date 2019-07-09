<?php

include("$_SERVER[DOCUMENT_ROOT]/vendor/autoload.php");
include("$_SERVER[DOCUMENT_ROOT]/config/init.php");

include_once("$_SERVER[DOCUMENT_ROOT]/config/jwtcore.php");
use \Firebase\JWT\JWT;


// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 





// files needed to connect to database
include("$_SERVER[DOCUMENT_ROOT]/config/database.php");
include("$_SERVER[DOCUMENT_ROOT]/classes/User.php");


 
    // get database connection
    $database = new Database($url);
    $db = $database->getConnection();
    
    // instantiate user object
    $user = new User($db);
    
    // retrieve given jwt here
    // get posted data
    $data = json_decode(file_get_contents("php://input"));
    
    // get jwt
    $jwt=isset($data->jwt) ? $data->jwt : "";
    
    // decode jwt here
    // if jwt is not empty
    if($jwt){
    
        // if decode succeed, show user details
        try {
    
            // decode jwt
            $decoded = JWT::decode($jwt, $key, array('HS256'));
    
            
            // set user property values
            
            $user->userName = $data->userName;
            $user->email = $data->email;
            $user->password = $data->password;
            $user->id = $decoded->data->id;

            
            // update user will be here
            if($user->update()){
                // regenerate jwt will be here
                // we need to re-generate jwt because user details might be different
                $token = array(
                    "iss" => $iss,
                    "aud" => $aud,
                    "iat" => $iat,
                    "nbf" => $nbf,
                    "data" => array(
                        "id" => $user->id,
                        "userame" => $user->userName,
                        "email" => $user->email
                    )
                );
                $jwt = JWT::encode($token, $key);
                
                // set response code
                http_response_code(200);
                
                // response in json format
                echo json_encode(
                        array(
                            "message" => "User was updated.",
                            "jwt" => $jwt,
                           
                        )
                    );
            }
            
            // message if unable to update user
            else{
                // set response code
                http_response_code(401);
            
                // show error message
                echo json_encode(array("message" => "Unable to update user."));
            }
        }
        // catch failed decoding will be here
        // if decode fails, it means jwt is invalid
        catch (Exception $e){
        
            // set response code
            http_response_code(401);
        
            // show error message
            echo json_encode(array(
                "message" => "Access denied. decode failed",
                "error" => $e->getMessage(),
    
            ));
        }
    }
    // error message if jwt is empty will be here
    // show error message if jwt is empty
    else{
    
        // set response code
        http_response_code(401);
    
        // tell the user access denied
        echo json_encode(array("message" => "Access denied."));
    }
?>