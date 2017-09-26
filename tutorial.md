# Create a realtime Trello-like Kanban feature using VueJS and Pusher
I have been using [Trello](https://trello.com) for a while and I must say it is really a good tool useful for planning all sorts of things. Trello employs a Kanban system where you can create boards that can contain multiple cards. Each card when created can be moved around freely around different boards. 


> 💡 **Kanban** is a method for visualizing the flow of work, in order to balance demand with available capacity and spot bottlenecks. Work items are visualized to give participants a view of progress and process, from start to finish. Team members [pull](https://en.wikipedia.org/wiki/Push%E2%80%93pull_strategy) work as capacity permits, rather than work being pushed into the process when requested.

In this article we are going to consider how we can use VueJS, Pusher and Laravel to create a very simple Trello-like clone with focus on realtime updates. In our sample application, the user should be able to create boards and start adding cards to them. The user should also be able to move the cards around and everything should happen in realtime.

Here is a screenshot of how the functionality will work:


![](https://www.dropbox.com/s/t60br3pwoqj8yvu/Create-a-realtime-Trello-like-Kanban-feature-using-VueJS-and-Pusher.gif?raw=1)

## Requirements for building the Trello-like Kanban feature

The requirements for following the tutorial are listed below:


- PHP 7.0 or newer installed on your machine.
- [Laravel CLI](https://laravel.com/docs/5.5/installation#installing-laravel) installed locally.
- [Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx) installed on locally.
- [Node & NPM](https://docs.npmjs.com/getting-started/installing-node) installed locally.
- Knowledge of PHP & Laravel framework.
- Basic knowledge of JavaScript (ES6 specifically).
- Basic knowledge of VueJS.
- A [Pusher](https://pusher.com) application.
- Curl installed and available on your terminal.

If you have fulfilled the requirements, then we can start the tutorial.


## Setting up your application

We need to start by creating a new Laravel application. In the command line run the command below to create a new Laravel application:


    $ laravel new appname
    $ cd appname

This will create a new Laravel application inside the directory `appname`. When it is done creating the application, it’ll then `cd` to the application folder.

Now to start a PHP server using artisan, run the command below:


    php artisan serve

This will start a server using PHP and you can access the server at the address: http://127.0.0.1:8000. I will advice that you leave this command running in the background and open a new Terminal tab or window.

Now that we have set up the application, we need the application to connect to a database. We will be using SQLite as our database engine. However, you can use MySQL if you feel more comfortable with it.

**Setting up your SQLite database**
Open the `.env` file in the root of your project directory. In the file look for the lines below:


    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=homestead
    DB_USERNAME=homestead
    DB_PASSWORD=secret

and replace it with the line below:


    DB_CONNECTION=sqlite

Then, once you have saved the environment file, you will need to create a new file named `database.sqlite` in the `database` directory.

Now we should have our database set up. To be sure, run the command below:


    pho artisan migrate

This should return a response similar to the text below:


    ❯ php artisan migrate
    Migration table created successfully.
    Migrating: 2014_10_12_000000_create_users_table
    Migrated:  2014_10_12_000000_create_users_table
    Migrating: 2014_10_12_100000_create_password_resets_table
    Migrated:  2014_10_12_100000_create_password_resets_table

Great, now we can move on.

**Setting up Pusher before starting our application**
The next thing we want to do is set up Pusher to work with our application. To do this, we need to make some changes. 

Open the `.env` file again and this time look for the `BROADCAST_DRIVER` and change this to `pusher`. Then scroll to the bottom of the file and add your Pusher credentials as seen below:


    BROADCAST_DRIVER=pusher
    
    // ... other keys
    
    PUSHER_APP_ID=ENTER_PUSHER_APP_ID_HERE
    PUSHER_APP_KEY=ENTER_PUSHER_APP_KEY_HERE
    PUSHER_APP_SECRET=ENTER_PUSHER_APP_SECRET_HERE


> 💡 **You can get the Pusher application credentials from the Pusher application dashboard.**

Next, open your terminal and run the command below to install the Pusher PHP SDK using composer:


    $ composer require pusher/pusher-php-server "~3.0"

Next, you should configure your Pusher credentials in the `config/broadcasting.php` configuration file. Open the file and scroll to the Pusher specific section and look for the options array. Now configure your options as seen below:


    'pusher' => [
        // ...other options
        'options' => [
            'encrypted' => true,
            'cluster'   => 'ENTER_PUSHER_APP_CLUSTER',    
        ],
    ],

Now save and exit the file. This completes the server-side configuration of Pusher, but we also need to configure the client-side.

We will be using Laravel Echo to communicate with Pusher.


> 💡 **Laravel Echo is a JavaScript library that makes it painless to subscribe to channels and listen for events broadcast by Laravel.**

To install Laravel Echo and the Pusher JavaScript library, open up your terminal and run the command below:


    $ npm install --save laravel-echo pusher-js

When the installation is complete, open the `resources/assets/js/bootstrap.js` file in your editor. Scroll to the bottom of the file, look for the code below and uncomment it:


    import Echo from 'laravel-echo'
    
    window.Pusher = require('pusher-js');
    
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: 'your-pusher-key'
    });

In the key, add your Pusher application key. Also, right under the `key` configuration, add and configure some new items as seen below:


    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: 'your-pusher-key',
        cluster: 'your-pusher-cluster',
        encrypted: true
    });

With this change, Pusher has been configured to work on both the client-side and the server-side. Great.


## Creating the API for your application using Laravel

Now we have successfully configured our application, we need to start creating the API for our application.

**Creating a model, database and migrating the database**
Let us start by creating the necessary table that we will need. Open your terminal and enter the command below:


    $ php artisan make:model Board -mc
    $ php artisan make:controller BoardCardController

The first command will create a new model `Board` and then we will use the `-mc` flag to create an accompanying migration and `BoardController`. The second command creates a new `BoardCardController`.

Open the newly created migration. and replace the content of the `up` method with the code below:


    public function up()
    {
        Schema::create('boards', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50);
            $table->text('cards')->default('[]');
            $table->timestamps();
        });
    }

This migration will create the `boards` table that we will store our boards and cards inside our boards.

Now go to your terminal and run the command below to run the migration:


    $ php artisan migrate

Open the `Board` model class and replace the contents with the following below:


    <?php
    namespace App;
    
    use Illuminate\Database\Eloquent\Model;
    
    class Board extends Model
    {
        protected $fillable = ['name', 'cards'];
    
        public function getCardsAttribute($value)
        {
            return !empty($value) ? json_decode($value, true) : [];
        }
    
        public function setCardsAttribute($value)
        {
            $this->attributes['cards'] = json_encode($value);
        }
    }

In the model class above, we have set the fillable property so we can create new boards and fill the attributes defined in the array. Without setting this we will get a `MassAssignmentException`.

Next we have the `getCardsAttribute` and the `setCardsAttribute` methods which are [Eloquent Accessors and Mutators](https://laravel.com/docs/5.5/eloquent-mutators) respectively. They allow you to modify attributes before saving them to database or retrieving them from the database.


> 💡 **Accessors and mutators allow you to format Eloquent attribute values when you retrieve or set them on model instances. For example, you may want to use the** [**Laravel encrypter**](https://laravel.com/docs/5.5/encryption) **to encrypt a value while it is stored in the database, and then automatically decrypt the attribute when you access it on an Eloquent model.**

**Using the controller and route to respond to API calls**

Now that we have created our database table and also our model, we need to add the controller logic and also routes to access the logic.

Open the routes file and let’s start creating the logic one endpoint at a time. Open the `routes/api.php` file and add the following routes:


    Route::post('/boards/{id}/cards', 'BoardCardController@create');
    Route::post('/boards', 'BoardController@create');
    Route::put('/boards', 'BoardController@update');
    Route::get('/boards', 'BoardController@index');

Above, we have defined 4 routes. The first is a `POST` route where we will create cards in a board. The second is a `POST` route where we will create a new board. The third is a `PUT` route where we will update the board when changes occur. The last route is a `GET` route that returns all the available boards.

Let us start adding logic to each route. For the first route, we need to update the `BoardCardController` we created before. Open the file and replace with the content below:


    <?php
    namespace App\Http\Controllers;
    
    use App\Board;
    use Illuminate\Http\Request;
    
    class BoardCardController extends Controller
    {
        public function create(Request $request, $id)
        {
            $board = Board::findOrFail($id);
    
            $data = $request->validate(['cards' => 'required']);
            
            $updated = $board->update($data);
    
            return response(['status' => $updated ? 'success' : 'error']);
        }
    }

In the method `create` above we are just fetching the board that match the `$id` and then updating that board with an array of cards received. Depending on the return value if the `$board-
>update` call, a success or error response is sent back to the client.

The next route to add logic for is `BoardController@create`. Open the `BoardController` file and `use` the `App\Board` class as seen below on top of the `use Illuminate\Http\Request` statement:


    use App\Board;

Now, in the body of the class, add a new method `create` as seen below:


    public function create(Request $request)
    {
        $data = $request->validate(['name' => 'required|between:1,50']);
    
        $board = Board::create($data + ['cards' => []]);
        
        return response(['status' => $board ? 'success' : 'error', 'board' => $board]);
    }

In the method above, we are validating the request to make sure there is a name and it is between 1 and 50 characters. Next, we create the board and then return a response based on the return value of the `Board::create` method call.

The next route is for `BoardController@update`. Add the following method to the same controller right under the create method:


    public function update(Request $request)
    {
        foreach ($request->get('boards') as $board) {
            Board::findOrFail($board['id'])->update($board);
        }
    
        return response(["status" => "success"]);
    }

In the method above, we loop through the boards received from the request and then for each of the boards, we update the board. This method will usually be called when cards are moved around multiple boards.

The final and simplest route method to create is `BoardController@index`. Add the method below to the controller:


    public function index()
    {
        return response(Board::all()->toArray());
    }

In the above code, we fetch all the boards, change them to an array and return them as a response.

**Testing our board API endpoints**
Now that we have added four endpoints, we will need to test them. Make sure your PHP server is still running in the background as said in the beginning of the article. You can always run the command `php artisan serve` to start the server again.

Let us test the endpoints starting with the first one. Run the command below to create a new board. You should receive a response with a status set to success.


    $ curl --header "Accept: application/json" \
           --data "name=Sample" 127.0.0.1:8000/api/boards
    
    // Sample response expected
    {
        "status": "success",
        "board": {
            "name": "Sample",
            "cards": [],
            "updated_at": "2017-09-26 05:06:40",
            "created_at": "2017-09-26 05:06:40",
            "id": 1
        }
    }

As seen above, it shows a `success` response with a `board` key that contains details of the board that was created.

Now let’s test the endpoint that creates cards. Run the command below in your terminal:


    $ curl --header "Accept: application/json" \
           --data "cards[][title]=Sample" \
           --data "cards[][title]=Sample2" 127.0.0.1:8000/api/boards/1/cards
         
    // Response expected
    {
        "status":"success"
    }

 
 Great! Now we will test the next endpoint. This is called when cards are moved around so we will do just that. We will send a request to move the card `Sample2` above the card `Sample`.
 

    $ curl --header "Accept: application/json" \
           -X PUT \
           --data "boards[0][id]=1" \
           --data "boards[0][cards][][title]=Sample2" \
           --data "boards[0][cards][][title]=Sample" \
           127.0.0.1:8000/api/boards
           
    // Response expected
    {
        "status":"success"
    }

 
The command above seems a little more complicated but its just sending an array of boards with the cards and that is what is updated to the board.

Now let’s text the last one, type in the command below:
 

    $ curl --header "Accept: application/json" 127.0.0.1:8000/api/boards
    
    // Sample expected response
    [
        {
            "id": 1,
            "name": "Sample",
            "cards": [
                {
                    "title": "Sample2"
                },
                {
                    "title": "Sample"
                }
            ],
            "created_at": "2017-09-26 05:15:26",
            "updated_at": "2017-09-26 05:28:14"
        }
    ]

Great so our API works as expected. Let us move on to creating the frontend using Bootstrap and Vue.


## Creating the Trello-like Kanban style with Bootstrap and Vue

The first thing we want to do is get an NPM package ([vuedraggable](https://github.com/SortableJS/Vue.Draggable)) to help us with the drag and drop feature using Vue. To get this package, run the command below:


    $ npm install --save-dev vuedraggable

Now let’s create the entry point to our web application using Laravel. Open the `routes/web.php` file and replace the contents with the code below:


    Route::view('/', 'welcome');

This is a new feature in Laravel 5.5 and you can read all about [what’s new here](https://blog.pusher.com/whats-new-laravel-5-5).

Now, open the `resources/views/welcome.blade.php` and replace the contents with the contents below:


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

This is a lot and what it does is create a blank UI. However, there is a reference to a Vue component in the code on `line 21` (the `boards` component which we will create this later). We also have a Vue event handler on `line 26` and some others scattered around the template. We will handle those soon.

Next, open the `resources/assets/sass/app.scss` and paste the code below after the last `@import` statement:


    html, body, #app {
        max-height: 100vh;
        height: 100vh;
    }
    #header {
        background: rgba(0,0,0,.15);
        overflow: hidden;
        padding: 5px 8px;
        position: relative;
        height: 40px;
        text-align: center;
        margin: 0 0 20px;
        .header-logo {
            background-position: top right;
            background-repeat: no-repeat;
            background-size: 5pc 30px;
            right: 0;
            top: 0;
            height: 30px;
            width: 5pc;
            position: absolute;
            text-align: center;
            bottom: 0;
            display: block;
            left: 50%;
            margin-left: -40px;
            top: 5px;
            text-align: center;
            -webkit-transition: .1s ease;
            transition: .1s ease;
            z-index: 2;
        }
    }
    #boards {
        .board {
            width: 304px;
            padding-left: 5px;
            padding-right: 5px;
            float: left;
            .kanban-wrapper {
                background-color: #E2E4E6;
                border-radius: 5px;
                overflow: hidden;
                .board-title {
                    padding: 10px;
                    h2 {
                        margin: 0;
                        font-size: 14px;
                        font-weight: 600;
                        color: #272727;
                    }
                }
                .cards {
                    list-style: none;
                    margin: 0;
                    padding: 0 10px;
                    > div {
                        min-height: 5px;
                        padding: 5px 0;
                    }
                    .card {
                        overflow: hidden;
                        padding: 8px;
                        background-color: #fff;
                        border-bottom: 1px solid #ccc;
                        border-radius: 3px;
                        cursor: pointer;
                        margin-bottom: 6px;
                        max-width: 300px;
                        min-height: 20px;
                        &:hover {
                            background-color: #edeff0;
                        }
                    }
                }
                .add-card {
                    color: #838c91;
                    display: block;
                    flex: 0 0 auto;
                    padding: 8px 10px;
                    position: relative;
                    &[disabled], &[disabled]:hover {
                        cursor: not-allowed;
                        text-decoration: none;
                    }
                }
            }
        }
    }

Next, open the `resources/assets/sass/_variables.scss` file and in there replace the `$body-bg` value with `rgb(0, 121, 191)`. Also, replace:


    $font-family-sans-serif: "Raleway", sans-serif;

with


    $font-family-sans-serif: "Roboto", "Raleway", sans-serif;

This will give the UI the look and feel of the Trello application.

**Adding Vue to the UI**
Now let’s do a little Vue. Open the `resources/assets/js/app.js` file and replace the code below:


    Vue.component('example', require('./components/Example.vue'));

with


    Vue.component('boards', require('./components/Boards.vue'));

This is simply registering a Vue component called `boards`. So let us create the component file. Create a new file called `Boards.vue` in the `resources/assets/js/components` directory. Now paste in the code below:


    <template>
        <div id="boards" class="clearfix">
            <div class="board" v-for="(board, index) in boards" :key="index">
                <div class="kanban-wrapper">
                    <div class="board-title">
                        <h2>{{board.name}}</h2>
                    </div>
                    <ul class="cards">
                        <draggable v-model="board.cards" :options="{group:'cards'}" @start="drag=true" @end="drag=false">
                            <li v-for="(card, id) in board.cards" :key="id" class="card">{{card.title}}</li>
                            <li class="input" :id="'card-'+index" v-show="board.showInputField">
                                <form action="#" method="post" @submit.prevent="createCard(board)">
                                    <div class="row">
                                        <div class="col-xs-12">
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="card" v-model="newCardName" placeholder="Card name..." aria-label="Card name...">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-primary" type="submit">Add</button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </li>
                        </draggable>
                    </ul>
                    <a href="#" class="add-card" @click.prevent="showAddInputField(board)">
                        <span :hidden="board.showInputField">Add Card...</span>
                        <span v-show="board.showInputField">Done...</span>
                    </a>
                </div>
            </div>
        </div>
    </template>

In this HTML block we have the template of the component defined between the opening and closing `template` tag. We use `v-for` to loop through all the boards, then for each of the boards, we use another `v-for` to loop and generate the cards.

In the cards loop, we are using the `draggable` component that comes with the [vuedraggable](https://github.com/SortableJS/Vue.Draggable) package we installed using NPM earlier. For each board we also register a click handler that will toggle the “Add Card” input form.

Now let’s go to the bottom of the same file and paste in the script section of the Vue component below the `template` closing tag:


    <script>
    import draggable from 'vuedraggable'
    export default {
        props: ["boards"],
        components: {draggable},
        data: () => {
            return {newCardName: undefined}
        },
        methods: {
            createCard: function (board) {
                if (this.newCardName && this.newCardName != "") {
                    board.cards.push({title: this.newCardName})
                    this.newCardName = undefined
                    
                    axios.post('/api/boards/'+board.id+'/cards', {'cards':board.cards})
                }
            },
            showAddInputField: function (board) {
                let toggleValue = board.showInputField ? undefined : true
                this.hideAllOpenInputFields()
                board.showInputField = toggleValue
            },
            hideAllOpenInputFields: function () {
                for (let index in this.boards) {
                    if (this.boards.hasOwnProperty(index)) {
                        this.$set(this.boards[index], 'showInputField', undefined);
                    }
                }
            }
        }
    }
    </script>

In the script above, we `import` the `draggable` component and register it in the `components` property. We also declared the `boards`  property from the `boards` component we set in the `welcome.blade.php` file. Finally we have a `newCardName` that we will use to track the name of any card being added.

In the `methods` object, we have the `createCard` method that creates a new card and then calls the API endpoint. We also have the `showAddInput` method that toggles the “Add Card” input form. Then finally we have the `hideAllOpenInputFields` that basically hides all open “Add Card” input fields. Save the file and exit.

Now open the `resources/assets/js/app.js` file. We will start adding some meat to our Vue application. Under the element selector `el: '#app``'` add a comma and then add the mounted method defined below:


    mounted() {
        // Get from API
        axios.get('/api/boards').then(response => {
            this.boards = response.data
            this.recloneBoards()
        })
    
        // Listen for Pusher updates
        Echo.channel('boards')
            .listen('.board.created', data => {
                this.boards.push(data.board)
                this.recloneBoards()
    
                $('#addBoard').modal('hide')
    
                this.isCreatingBoard = false
                this.newBoardName = undefined
            })
            .listen('.card.created', data => {
                this.boards.forEach(function(board, index) {
                    if (board.id === data.board.id) {
                        this.$set(this.boards[index], "cards", data.board.cards);
                    }
                }, this);
                this.recloneBoards()
            })
            .listen('.updated', data => {
                console.log(data)
                this.$set(this, 'boards', data.boards)
                this.recloneBoards()
            })
    },

The `mounted` method is called automatically by Vue and so we will use that to load our API data using [Axios](https://github.com/mzabriskie/axios) which comes bundled and configured by default on Laravel. Then we use Laravel Echo to subscribe to a channel and then listen for events on that channel.

In the code above we are listening for 3 events: `.board.created`, `.card.created`, and `.updated`.
 

> 💡 **The dot (.) in front of the names of the events are compulsory because when we are using Laravel to broadcast and we don’t explicitly specify the alias of the event, Laravel automatically adds a namespace to the event name. However, we will be specifying the name of each event when pushing from Laravel hence, the dot is compulsory.**  

In the three event listeners above, we are pretty much doing the same thing, we are getting the data and updating the `boards` property. Then we are also calling a `recloneBoards` method which we will create later.

After pasting the `mounted` method, paste the `data` method right after it:


    data: () => {
        return {
            boards: [],
            cloneBoards: [],
            isCreatingBoard: false,
            newBoardName: undefined,
            recloneBoards: function () {
                this.cloneBoards = this.boards.map(a => Object.assign({}, a))
            }
        }
    },

This method simply registers reactive properties that will be available to the Vue application instance. The property names make it clear what they do. The `recloneBoards` method will simply keep a pristine version of the boards in `cloneBoards` so we know when `boards` has been modified.

Finally, under the `data` method, we past the last two blocks of code:


    methods: {
        addNewBoard: function () {
            if (this.newBoardName && this.newBoardName != "") {
                this.isCreatingBoard = true
                axios.post('/api/boards', {name: this.newBoardName})
            }
        }
    },
    watch: {
        boards: {
            handler: function (newValue, oldValue) {
                var vm = this
                let boards = newValue.filter( function( p, idx ) {
                    return Object.keys(p).some( function( prop ) {
                        var diff = p[prop] !== vm.cloneBoards[idx][prop];
                        if (diff && (prop !== 'showInputField' && prop === 'cards')) {
                            vm.cloneBoards[idx][prop] = p[prop]
                            return true
                        }
                    })
                });
                if (boards.length > 0) {
                    axios.put('/api/boards', {boards: boards})
                }
            },
            deep: true
        }
    }

In the `methods`  property, we add a method `addNewBoard` that adds a new board to the list of boards and calls the endpoint with the changes made to the board.

In the `watch` method, we can define properties we want to actively watch for changes. The property we are watching for changes is `boards`. Every time the `boards` property is modified, this callback `handler` is run and the code inside executed.

In the handler, we filter the boards to check every board to see what exactly changed in the board. If it is the card that changed, then we assume it was a valid change and then we return that board. Since we used the `filter` function, it’ll return an array of all the boards that have been filtered based on the logic. We can then send the boards over to the API.

With that we have completed the Vue aspect. In your terminal run the command below:


    $ npm install
    $ npm run dev

This should install all the needed NPM dependencies and then compile all the assets to our application. There is one final thing to do before we test our application though. We need to send events to Pusher from Laravel every time there is an action so Vue can pick it up on the client-side.


## Broadcasting events to our Vue application using Laravel

Open the terminal and enter the commands below:


    $ php artisan make:event BoardCreated
    $ php artisan make:event BoardUpdated
    $ php artisan make:event BoardCardCreated

This will create three event classes in the `app/events` directory. let’s start updating them. If you do not know about events and broadcasting in Laravel, you can [read up here](https://laravel.com/docs/5.5/events#registering-events-and-listeners).

Now open the first class `BoardCreated` and in there paste in the following: 


    <?php
    
    namespace App\Events;
    
    use Illuminate\Broadcasting\Channel;
    use Illuminate\Queue\SerializesModels;
    use Illuminate\Foundation\Events\Dispatchable;
    use Illuminate\Broadcasting\InteractsWithSockets;
    use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
    
    class BoardCreated implements ShouldBroadcast
    {
        use Dispatchable, InteractsWithSockets, SerializesModels;
        
        public $board;
    
        public function __construct($board)
        {
            $this->board = $board;
        }
    
        public function broadcastOn()
        {
            return new Channel('boards');
        }
    
        public function broadcastAs()
        {
            return 'board.created';
        }
    }

This is a simple Event class, and we have specified the channel to broadcast on, and also the name of the event to broadcast as. Without specifying the `broadcastAs` alias, Laravel will automatically use the namespace and class name as the event name.

Do the same for the other two classes as defined below: 

**BoardCardCreated class:**

    <?php
    
    namespace App\Events;
    
    use Illuminate\Broadcasting\Channel;
    use Illuminate\Queue\SerializesModels;
    use Illuminate\Foundation\Events\Dispatchable;
    use Illuminate\Broadcasting\InteractsWithSockets;
    use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
    
    class BoardCardCreated implements ShouldBroadcast
    {
        use Dispatchable, InteractsWithSockets, SerializesModels;
        
        public $board;
    
        public function __construct($board)
        {
            $this->board = $board;
        }
    
        public function broadcastOn()
        {
            return new Channel('boards');
        }
    
        public function broadcastAs()
        {
            return 'card.created';
        }
    }

**BoardUpdated class:**

    <?php
    
    namespace App\Events;
    
    use Illuminate\Broadcasting\Channel;
    use Illuminate\Queue\SerializesModels;
    use Illuminate\Foundation\Events\Dispatchable;
    use Illuminate\Broadcasting\InteractsWithSockets;
    use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
    
    class BoardsUpdated implements ShouldBroadcast
    {
        use Dispatchable, InteractsWithSockets, SerializesModels;
        
        public $boards = [];
    
        public function __construct(array $boards)
        {
            $this->boards = $boards;
        }
    
        public function broadcastOn()
        {
            return new Channel('boards');
        }
    
        public function broadcastAs()
        {
            return 'updated';
        }
    }

After this is complete, we need to dispatch (or call) these events when the respective actions take place.

Open the `BoardController` and in the `create` method after the `Board::create` method is called, paste in the code below:


    event(new \App\Events\BoardCreated($board));

Next, scroll to the `update` method and right after the for loop, paste the following below it:


    event(new \App\Events\BoardUpdated(Board::all()->toArray()));

Next open the `BoardCardController` class and go to the `create` method and after the `$board->update($data)` call, paste in the following code:


    if ($updated) {
        event(new \App\Events\BoardCardCreated($board));
    }

That’s all! Everything is complete. 

Launch your browser and go to the URL of your application. It should be http://127.0.0.1:8000 if you used the `php artisan serve` command.


## Conclusion

In this article, we have recreated the Trello-like Kanban feature with realtime updates using Vue and Pusher. Hopefully you have learnt a thing or two on how you can harness the power of Vue and Pusher to create realtime web applications.

If you have any questions or feedback, please leave it as a comment on the article. The source code to the article is available on [GitHub](https://github.com/neoighodaro/trello-kanban-cards-clone).

