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

                // Send to API
                axios.post('/api/boards/'+board.id+'/cards', {'cards':board.cards}).then(response => {})
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
