<?php 

class Response {

    public function success($data){
        return json_encode([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function errorResponse($message, $encode = true, $exit = false){
        $data = [
            'status' => 'error',
            'message' => $message
        ];
        if($exit){
            echo json_encode($data);
            exit();
        }
        return $encode ? json_encode($data) : $data;
    }


}