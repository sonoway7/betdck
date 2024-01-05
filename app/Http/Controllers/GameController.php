<?php namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Redis;
use Illuminate\Http\Request;
use RecursiveIteratorIterator;
use RecursiveArrayIterator;
use Illuminate\Support\Facades\Log;
use App\User;
use App\Location;
use Ramsey\Uuid\Uuid;
use Auth;
use App\Salsa;
use App\Events\BalanceUpdate;
class GameController extends Controller
{
    private $balance;
    private $token;
    private $userLogged;

    public function __construct()
    {
        parent::__construct();
        $this->redis = Redis::connection();
    }

    public function login(Request $r){
        $brand_id = $r->input('brand_id');
        $sign = $r->input('sign');
        $token = $r->input('token');
        $brand_uid = $r->input('brand_uid');
        $currency = $r->input('currency');
        $user = User::where('username', $brand_uid)->first();

        $originalString = $user->balance;
        $val = str_replace(",",".",$originalString);
        $val = preg_replace('/\.(?=.*\.)/', '', $originalString);

        $data = [
            'code' => 1000,
            'msg' => 'Success',
            'data' => [
                'brand_uid' => $brand_uid,
                'currency' => $currency,
                'balance' => floatval($val)
            ]
        ];

        return $data;
    }

    public function wager(Request $request){
        $brand_uid = $request->input('brand_uid');
        $currency = $request->input('currency');
        $amount = $request->input('amount');
        $user = User::where('username', $brand_uid)->first();
    
        if ($user && $user->balance >= $amount) {
            $user->update(['balance' => $user->balance - $amount]);
    
            $response = [
                'code' => 1000,
                'msg' => 'Success',
                'data' => [
                    'brand_uid' => $brand_uid,
                    'currency' => $currency,
                    'balance' => $user->balance
                ]
            ];
        } else {
            $response = [
                'code' => 5003,
                'msg' => 'Insufficient funds',
                'data' => null
            ];
        }
    
        return $response;
    }

    public function endWager(Request $request){

        $brand_id = $request->input('brand_id');
        $sign = $request->input('sign');
        $brand_uid = $request->input('brand_uid');
        $currency = $request->input('currency');
        $amount = $request->input('amount');
        $round_id = $request->input('round_id');
        $wager_id = $request->input('wager_id');
        $provider = $request->input('provider');
        $is_endround = $request->input('is_endround');
        $game_result = $request->input('game_result');
        $user = User::where('username', $brand_uid)->first();

        $user->update(['balance' => $user->balance + $amount]);
        $user->update(['requery' => $user->requery + $amount]);
    
        $response = [
            'code' => 1000,
            'msg' => 'Success',
            'data' => [
                'brand_uid' => $brand_uid,
                'currency' => $currency,
                'balance' => $user->balance
            ]
        ];
    
        return $response;
    }

    public function appendWagger(Request $request){
        $brand_id = $request->input('brand_id');
        $sign = $request->input('sign');
        $brand_uid = $request->input('brand_uid');
        $currency = $request->input('currency');
        $amount = $request->input('amount');
        $game_id = $request->input('game_id');
        $game_name = $request->input('game_name');
        $round_id = $request->input('round_id');
        $wager_id = $request->input('wager_id');
        $provider = $request->input('provider');
        $description = $request->input('description');
        $is_endround = $request->input('is_endround');

        $user = User::where('username', $brand_uid)->first();

        if ($user && $user->balance >= $amount) {
            $user->update(['balance' => $user->balance - $amount]);
    
            $response = [
                'code' => 1000,
                'msg' => 'Success',
                'data' => [
                    'brand_uid' => $brand_uid,
                    'currency' => $currency,
                    'balance' => $user->balance
                ]
            ];
        } else {
            $response = [
                'code' => 5003,
                'msg' => 'Insufficient funds',
                'data' => null
            ];
        }
    
        return $response;
    }

    public function cancelWager(Request $request){
        $brand_id = $request->input('brand_id');
        $sign = $request->input('sign');
        $brand_uid = $request->input('brand_uid');
        $currency = $request->input('currency');
        $round_id = $request->input('round_id');
        $wager_id = $request->input('wager_id');
        $provider = $request->input('provider');
        $wager_type = $request->input('wager_type');
        $is_endround = $request->input('is_endround');

        $user = User::where('username', $brand_uid)->first();
    
    
        $response = [
            'code' => 1000,
            'msg' => 'Success',
            'data' => [
                'brand_uid' => $brand_uid,
                'currency' => $currency,
                'balance' => $user->balance
            ]
        ];
    
        return $response;
    }
    

    public function webhook(Request $request)
    {
        $xmlstring = $request->getContent();
        $xml = simplexml_load_string($xmlstring, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json, true);

        $method = $array['Method']['@attributes']['Name'];
        $params = $array['Method']['Params'];

        $this->token = $params['Token']['@attributes']['Value'];
        $user = User::where('salsa_token', $this->token)->first();
        if($user == null){
        if(isset($params['GameReference']) && $params['GameReference']['@attributes']['Value'] != null){
        $userNovoToken = User::where('hash_salsa', $params['GameReference']['@attributes']['Value'])->first();
        $response =
        "<PKT>
            <Result Name='GetAccountDetails' Success='0'>
                <Returnset>
                    <Error Value='Token Expired|Error retrieving Token|Invalid request' />
                    <ErrorCode Value='2' />
                    <Balance Type='int' Value='$userNovoToken->balance' />
                </Returnset>
            </Result>
        </PKT>";
        return response($response)
        ->header('Content-Type', 'text/xml; charset=UTF-8');
        }else{
            $response =
            "<PKT>
                <Result Name='GetAccountDetails' Success='0'>
                    <Returnset>
                        <Error Value='Token Expired|Error retrieving Token|Invalid request' />
                        <ErrorCode Value='2' />
                    </Returnset>
                </Result>
            </PKT>";
            return response($response)
            ->header('Content-Type', 'text/xml; charset=UTF-8');
        }
    }
        if (isset($params['GameReference']) && $params['GameReference']['@attributes']['Value'] != null) {
            $user->update(['hash_salsa' => $params['GameReference']['@attributes']['Value']]);
        }

        $user->balance = $user->balance * 100;
        


        switch ($method):

            case 'GetAccountDetails':
                return $this->GetAccountDetails($params, $user, $method);
                break;

            case 'GetBalance':
                return $this->GetBalance($params, $user, $method);
                break;

            case 'PlaceBet':
                return $this->PlaceBet($params, $user, $method);
                break;

            case 'AwardWinnings':
                return $this->AwardWinnings($params, $user, $method);
                break;

            case 'RefundBet':
                return $this->RefundBet($params, $user, $method);
                break;

            case 'ChangeGameToken':
                return $this->ChangeGameToken($params, $user, $method);
                break;

        endswitch;
    }

    public function compareHash($params, $token, $method) {   

        $hash = $params['Hash']['@attributes']['Value'];

        $secret = '1fd676e6dc1c29496e68d81943f06005';
        $secret2 = 'fc8b096c103702de9fa03833993f91dd';

        unset($params['Hash']);
        
        $flattenedParams = $this->flattenArray($params, $method);

        $computedHash = hash('sha256', $flattenedParams . $secret);

        if($computedHash == $hash){
            return true;
        }else{
            $computedHash2 = hash('sha256', $flattenedParams . $secret2);
            if($computedHash2 == $hash){
                return true;
            }else{
                return false;
            }
        }
    }

    protected function flattenArray($params, $method) {
        $result = [];
    
        switch ($method) {
            case 'GetAccountDetails':
            case 'GetBalance':
                $result[] = $params['Token']['@attributes']['Value'];
                break;
            
            case 'AwardWinnings':
                $result[] = $params['TransactionID']['@attributes']['Value'];
                $result[] = $params['WinReferenceNum']['@attributes']['Value'];
                $result[] = $params['Token']['@attributes']['Value'];
                break;
            case 'PlaceBet':
            case 'RefundBet':
                $result[] = $params['TransactionID']['@attributes']['Value'];
                $result[] = $params['BetReferenceNum']['@attributes']['Value'];
                $result[] = $params['Token']['@attributes']['Value'];
                break;
    
            case 'ChangeGameToken':
                $result[] = $params['NewGameReference']['@attributes']['Value'];
                $result[] = $params['Token']['@attributes']['Value'];
                break;
    
            default:
                return 'nada encontrado.';
        }
    
        $concatedString = implode('', $result);
    
        return $concatedString;
    }
    
    public function getAccountDetails($params, $user, $method) {
    
        if ($this->token) {
            
            if ($this->compareHash($params, $user->salsa_token, $method)) {

                $response = "<PKT>
                    <Result Name='GetAccountDetails' Success='1'>
                        <Returnset>
                            <Token Type='string' Value='$user->salsa_token' />
                            <LoginName Type='string' Value='$user->username' />
                            <Currency Type='string' Value='BRL' />
                            <Country Type='string' Value='BR' />
                            <Birthdate Type='date' Value='1988-08-02' />
                            <Registration Type='date' Value='2010-05-05' />
                            <Gender Type='string' Value='m' />
                        </Returnset>
                    </Result>
                </PKT>";
            } else {
                $response = "<PKT>
                    <Result Name='GetAccountDetails' Success='0'>
                        <Returnset>
                            <Error Value='Invalid Hash.' />
                            <ErrorCode Value='7000' />
                        </Returnset>
                    </Result>
                </PKT>";
            }
        } else {
            $response = "<PKT>
                <Result Name='GetAccountDetails' Success='0'>
                    <Returnset>
                        <Error Value='Error retrieving Token.' />
                        <ErrorCode Value='1' />
                    </Returnset>
                </Result>
            </PKT>";
        }
    
        return response($response)
        ->header('Content-Type', 'text/xml; charset=UTF-8');
    }
    

    public function GetBalance($params, $user, $method){
    
        if ($this->token) {
            if ($this->compareHash($params, $this->token, $method)) {
                $response = "<PKT>
                    <Result Name='GetBalance' Success='1'>
                        <Returnset>
                            <Token Type='string' Value='$user->salsa_token' />
                            <Balance Type='int' Value='$user->balance' />
                            <Currency Type='string' Value='BRL' />
                        </Returnset>
                    </Result>
                </PKT>";
            } else {
                $response = "<PKT>
                    <Result Name='GetBalance' Success='0'>
                        <Returnset>
                            <Error Value='Invalid Hash.' />
                            <ErrorCode Value='7000' />
                        </Returnset>
                    </Result>
                </PKT>";
            }
        } else {
            $response = "<PKT>
                <Result Name='GetBalance' Success='0'>
                    <Returnset>
                        <Error Value='Insufficient funds.' />
                        <ErrorCode Value='6' />
                    </Returnset>
                </Result>
            </PKT>";
        }
    
        return response($response)
        ->header('Content-Type', 'text/xml; charset=UTF-8');
    }

    public function PlaceBet($params, $user, $method){
        if ($this->token) {
            if ($this->compareHash($params, $this->token, $method)) {
                
                if($user->balance < $params['BetAmount']['@attributes']['Value']){
                    $response = "<PKT>
                        <Result Name='PlaceBet' Success='0'>
                            <Returnset>
                                <Error Value='Not enoght credits|Insufficient funds' />
                                <ErrorCode Value='6' />
                                <Balance Type='int' Value='$user->balance' />
                            </Returnset>
                        </Result>
                    </PKT>";
    
                    return response($response)
                    ->header('Content-Type', 'text/xml; charset=UTF-8');
                };

                if ($params['BetReferenceNum']['@attributes']['Value'] == $user->bet_reference_num){
                    $resultValue = $user->balance;
                }else{
                    $resultValue = $user->balance - $params['BetAmount']['@attributes']['Value'];
                }
     
                $user->update(['bet_reference_num' =>$params['BetReferenceNum']['@attributes']['Value']]);
  
                $user->update(['balance' => $resultValue / 100]);
                event(new BalanceUpdate($user->balance));

                $response = "<PKT>
                    <Result Name='PlaceBet' Success='1'>
                        <Returnset>
                            <Token Type='string' Value='$user->salsa_token' />
                            <Balance Type='int' Value='$resultValue' />
                            <Currency Type='string' Value='BRL' />
                            <ExtTransactionID Type='long' Value='{$params['TransactionID']['@attributes']['Value']}' />
                        </Returnset>
                    </Result>
                </PKT>";
            } else {
                $response = "<PKT>
                    <Result Name='PlaceBet' Success='0'>
                        <Returnset>
                            <Error Value='Invalid Hash.' />
                            <ErrorCode Value='7000' />
                        </Returnset>
                    </Result>
                </PKT>";
            }
        } else {
            $response = "<PKT>
                <Result Name='PlaceBet' Success='0'>
                    <Returnset>
                        <Error Value='Transaction not found.' />
                        <ErrorCode Value='7' />
                    </Returnset>
                </Result>
            </PKT>";
        }

        
    
        return response($response)
        ->header('Content-Type', 'text/xml; charset=UTF-8');
    }

    public function AwardWinnings($params, $user, $method){

        if ($this->token) {
            if ($this->compareHash($params, $this->token, $method)) {

                if ($params['WinReferenceNum']['@attributes']['Value'] == $user->win_reference_num){
                    $resultValue = $user->balance;
                }else{
                    $resultValue = $user->balance + $params['WinAmount']['@attributes']['Value'];
                }

                $newRequery = $user->requery + ($params['WinAmount']['@attributes']['Value'] * 100);

                $user->update(['requery' => $newRequery]);

                $user->update(['win_reference_num' =>$params['WinReferenceNum']['@attributes']['Value']]);

                $user->update(['balance' => $resultValue / 100]);
                event(new BalanceUpdate($user->balance));

                $response = "<PKT>
                    <Result Name='AwardWinnings' Success='1'>
                        <Returnset>
                            <Token Type='string' Value='$user->salsa_token' />
                            <Balance Type='int' Value='$resultValue' />
                            <Currency Type='string' Value='BRL' />
                            <ExtTransactionID Type='long' Value='{$params['TransactionID']['@attributes']['Value']}' />
                            <AlreadyProcessed Type='bool' Value='true' />
                        </Returnset>
                    </Result>
                </PKT>";
            } else {
                $response = "<PKT>
                    <Result Name='AwardWinnings' Success='0'>
                        <Returnset>
                            <Error Value='Invalid Hash.' />
                            <ErrorCode Value='7000' />
                        </Returnset>
                    </Result>
                </PKT>";
            }
        } else {
            $response = "<PKT>
                <Result Name='AwardWinnings' Success='0'>
                    <Returnset>
                        <Error Value='Transaction not found.' />
                        <ErrorCode Value='7' />
                    </Returnset>
                </Result>
            </PKT>";
        }
    
        return response($response)
        ->header('Content-Type', 'text/xml; charset=UTF-8');
    }

    public function RefundBet($params, $user, $method){

        if ($params['TransactionID']['@attributes']['Value'] == $user->transaction){
            $resultValue = $user->balance;
        }else{
            $resultValue = $user->balance + $params['RefundAmount']['@attributes']['Value'];
        }

        $user->update(['transaction' =>$params['TransactionID']['@attributes']['Value']]);

        $user->update(['balance' => $resultValue / 100]);
        event(new BalanceUpdate($user->balance));

        if ($this->token) {
            if ($this->compareHash($params, $this->token, $method)) {
                $response = "<PKT>
                    <Result Name='RefundBet' Success='1'>
                        <Returnset>
                            <Token Type='string' Value='$user->salsa_token' />
                            <Balance Type='int' Value='$resultValue' />
                            <Currency Type='string' Value='BRL' />
                            <ExtTransactionID Type='long' Value='{$params['TransactionID']['@attributes']['Value']}' />
                            <AlreadyProcessed Type='bool' Value='true' />
                        </Returnset>
                    </Result>
                </PKT>";
            } else {
                $response = "<PKT>
                    <Result Name='RefundBet' Success='0'>
                        <Returnset>
                            <Error Value='Invalid Hash.' />
                            <ErrorCode Value='7000' />
                        </Returnset>
                    </Result>
                </PKT>";
            }
        } else {
            $response = "<PKT>
                <Result Name='RefundBet' Success='0'>
                    <Returnset>
                        <Error Value='Transaction not found.' />
                        <ErrorCode Value='7' />
                        <Currency Type='string' Value='BRL' />
                    </Returnset>
                </Result>
            </PKT>";
        }
    
        return response($response)
        ->header('Content-Type', 'text/xml; charset=UTF-8');
    }

    public function ChangeGameToken($params, $user, $method){
    
        if ($this->token) {
            if ($this->compareHash($params, $this->token, $method)) {
                $response = "<PKT>
                    <Result Name='ChangeGameToken' Success='1'>
                        <Returnset>
                            <NewToken Type='string' Value='{$params['Token']['@attributes']['Value']}' />
                            <Balance Type='int' Value='$user->balance' />
                        </Returnset>
                    </Result>
                </PKT>";
            } else {
                $response = "<PKT>
                    <Result Name='ChangeGameToken' Success='0'>
                        <Returnset>
                            <Error Value='Invalid Hash.' />
                            <ErrorCode Value='7000' />
                        </Returnset>
                    </Result>
                </PKT>";
            }
        } else {
            $response = "<PKT>
                <Result Name='ChangeGameToken' Success='0'>
                    <Returnset>
                        <Error Value='Transaction not found.' />
                        <ErrorCode Value='7' />
                    </Returnset>
                </Result>
            </PKT>";
        }
    
        return response($response)
        ->header('Content-Type', 'text/xml; charset=UTF-8');
    }

    
    

public function playGame($game_id)
{
    $namespace = Uuid::NAMESPACE_DNS;

    if($this->user == null){
    $userLogged = 'demo';
    }else{
    $userLogged = $this->user->username;
    }

    $game = Salsa::where('id', $game_id)->first();

    if (!$game) {
        return response()->json(['error' => 'Jogo não encontrado'], 404);
    }

    $token = Uuid::uuid5($namespace, $userLogged)->toString();
    if($game->pn == 'playbet'){
    $url = "$game->url_dev?token=$token&pn=$game->pn&lang=$game->lang&game=$game->game&currency=BRL&type=CHARGED";
    }else{
    $url = "$game->url_dev?token=$token&pn={$game->pn}&lang={$game->lang}&game={$game->game}";
    }
    Log::info('URL: ' . $url);
    User::where('username', $userLogged)->update(['salsa_token' => $token]);

    return view('pages.superHotBingo', compact('url'));
}

public function saveLocation(Request $request)
{
    $locationData = $request->input('location');

    $location = new Location();

    $location->location = $locationData;

    $location->save();

    return response()->json(['message' => 'Location saved successfully']);
}

    public function gameList(){
        $chave_api = 'C93929113F374C90AB66CD206C901785';
        $id_marca = 'S119001';
        $api_url = 'https://gaming.stagedc.net';
        
        $data = [
            'brand_id' => $id_marca,
            'sign' => strtoupper(md5($id_marca . $chave_api)),
        ];
        
        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);
        
        $response = $client->request('POST', $api_url . '/dcs/getGameList', [
            'json' => $data
        ]);
        
        $responseBody = json_decode($response->getBody(), true);
        
        // Adiciona a informação das imagens locais
        $jogos = $responseBody['data'];
        foreach ($jogos as $index => $game) {
            $idJogo = $game['game_id'];
            $caminhoPasta = public_path("images/games");
            $nomeImagem = $this->encontrarNomeImagem($caminhoPasta, $idJogo);
    
            if ($nomeImagem) {
                $jogos[$index]['local_image'] = str_replace(["'", ' ', " ", "(NFD)"], '', $nomeImagem);
            } else {
                $jogos[$index]['local_image'] = null;
            }
        }
        $jogos = array_filter($jogos, function ($game) {
            return $game['local_image'] !== null;
        });
    
        $responseBody['data'] = $jogos;
        return response()->json($responseBody);
    }
    
    private function encontrarNomeImagem($caminho, $idJogo) {
        $arquivos = scandir($caminho);
        
        foreach ($arquivos as $arquivo) {
            if (strpos($arquivo, strval($idJogo)) !== false) {
                return "images/games/" . $arquivo;
            }
        }
    
        return "";
    }



}