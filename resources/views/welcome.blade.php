<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Laravel</title>
        <link href="https://fonts.googleapis.com/css?family=Roboto:100,400,600" rel="stylesheet" type="text/css">
        <link href="{{ asset('css/app.css') }}" type="text/css" rel="stylesheet">
        <meta name="csrf-token" content="{{ csrf_token() }}" />
    </head>
    <body>
        <div id="app">
            <div id="header">
                <span class="header-logo" style="background-image:url({{asset('img/logo.png')}}"></span>
                <div class="pull-right">
                    <a href="#" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addBoard">New Board</a>
                </div>
            </div>

            <div class="container-fluid">
                <boards :boards="boards"></boards>
            </div>

            <div class="modal fade" id="addBoard" role="dialog" aria-labelledby="addBoardLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <form action="#" @submit.prevent="addNewBoard()">
                            <div class="modal-header">
                                <h5 class="modal-title">Add new board</h2>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <input type="text" class="form-control" placeholder="Board name..." name="board" v-model="newBoardName">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-primary" type="submit" :disabled="isCreatingBoard || !newBoardName">Create Board</button>
                                <button type="button" class="pull-left btn btn-default" data-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script src="js/app.js"></script>
    </body>
</html>
