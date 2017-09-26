
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

Vue.component('boards', require('./components/Boards.vue'));

const app = new Vue({
    el: '#app',
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
});
