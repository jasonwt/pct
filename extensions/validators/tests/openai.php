<?php


/*


The OpenAI GPT-3 API provides several query parameters that you can use to customize your requests and control the behavior of the model. Some of the most commonly used parameters include:

prompt: The prompt text that you want the model to complete.
model: The ID of the GPT-3 model that you want to use.
temperature: Controls the randomness and creativity of the model's responses. A higher temperature value will result in more unpredictable and varied responses, while a lower temperature will result in more straightforward and predictable answers.
top_p: Controls the diversity of the model's responses by specifying the maximum proportion of tokens (words) to consider for each response.
max_tokens: The maximum number of tokens (words) that you want the model to generate in each response.
n: The number of responses that you want the model to generate for each prompt.
stream: Indicates whether you want the model to stream the generated tokens one by one as they are generated, or to wait until the entire response is generated before returning the results.
presence_penalty: Controls the model's confidence in its responses by penalizing responses that are too similar to the prompt text.
best_of: Specifies the number of different models that you want the API to use to generate each response, and returns the response with the highest quality score.
These are just a few of the many query parameters that are available in the OpenAI GPT-3 API. For a full list of available parameters and more information about how to use them, you can refer to the OpenAI API documentation: https://beta.openai.com/docs/api-reference/




*/


$openai = new Openai();

$openai->request("text-davinci-002", "implement huffmans compression and decompression class in php", 4000);

class Openai{
    private function secret_key(){
        return $secret_key = 'Bearer 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';
    }

    public function request($engine, $prompt, $max_tokens){ 

        $request_body = [
        "prompt" => $prompt,
        "max_tokens" => $max_tokens,
        "temperature" => 0.5,
        "top_p" => 0.5,
        "presence_penalty" => 0.0,
        "frequency_penalty"=> 0.0,
        "best_of"=> 1,
        "stream" => false,
        ];

        $postfields = json_encode($request_body);
        $curl = curl_init();
        curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.openai.com/v1/engines/" . $engine . "/completions",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $postfields,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: ' . $this->secret_key()
        ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "Error #:" . $err;
        } else {
            print_r(json_decode($response));
        }

    }

    public function search($engine, $documents, $query){ 

        $request_body = [
        "max_tokens" => 10,
        "temperature" => 0.7,
        "top_p" => 1,
        "presence_penalty" => 0.75,
        "frequency_penalty"=> 0.75,
        "documents" => $documents,
        "query" => $query
        ];

        $postfields = json_encode($request_body);
        $curl = curl_init();
        curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.openai.com/v1/engines/" . $engine . "/search",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $postfields,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: ' . $this->secret_key()
        ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "Error #:" . $err;
        } else {
            echo $response;

            print_r($response);
        }

    }

}