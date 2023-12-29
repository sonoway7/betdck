@extends('layout')

@section('content')
    <link rel="stylesheet" href="/css/mines.css">
    <link rel="stylesheet" href="/css/tower.css">

    <style>
        .tower_tower__1ms3K {
            position: relative;
            width: 54vw; 
            height: 40vw;
            margin: 5vh auto; 
            overflow: hidden;
        }

        .tower_tower__1ms3K iframe {
            width: 100%;
            height: 100%;
            border: 0; /* Remova a borda se desejar */
        }
    </style>

    <div class="section game-section">
        <div class="container">
            <div class="game">
                <div class="game-component">
                    <div class="tower_tower__1ms3K">
                    <iframe src="{{ $url }}"></iframe>
                    </div>

                    @guest
                    <div class="game-sign">
                        <div class="game-sign-wrap">
                            <div class="game-sign-block auth-buttons">
                                Você precisa estar logado para jogar
                                <button type="button" class="btn" id="loginRegister">Entrar ou Cadastrar</button>
                            </div>
                        </div>
                    </div>
                    @endguest
                </div>
            </div>
        </div>
    </div>
@endsection
