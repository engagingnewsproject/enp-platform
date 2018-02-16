/**
* Manages localStorage of current question state
* and previous states of the current decision tree path
* (so people can go back to previous questions)
*/
function TreeHistory(options) {
    var _Tree,
        _history,
        _historyStorageName,
        _currentIndex, // where in the history we're at (current state)
        _currentIndexStorageName;

        // keep an array of observers
        this.observers = []

    /**
    * Private functions
    */
    // save the passed history to localStorage and set the global _history to make sure everything is in sync
    var _saveHistory = function(history) {
        _history = history;
        localStorage.setItem(_historyStorageName, JSON.stringify(_history));
    }

    var _saveCurrentIndex = function(currentIndex) {
        _currentIndex = currentIndex
        localStorage.setItem(_currentIndexStorageName, JSON.stringify(_currentIndex))
    }

    // getters
    this.getTree = function() { return _Tree}
    this.getHistory = function() { return _history}
    this.getCurrentIndex = function() { return _currentIndex}

    // setters
    /**
    * Clears the history and currentIndex to an empty state
    */
    this.clearHistory = function() {
        let tree_id;
        tree_id = this.getTree().getTreeID()
        // create as the Tree intro state with overview and index at start.
        let history = [
            {type: 'intro', id: tree_id},
            {type: 'overview', id: tree_id}
        ];
        let currentIndex = 0;

        _saveHistory(history)
        _saveCurrentIndex(currentIndex)
    }

    /**
    * Sets the parent Tree
    */
    this.setTree = function(Tree) {
        // only let it be set once
        if(_Tree === undefined) {
            _Tree = Tree
        }
        return _Tree
    }

    /**
    * Sets variables and decides what state we'll init to
    */
    this.init = function() {
        let treeID,
            checkState;

        // get the tree id
        treeID = this.getTree().getTreeID()

        // set global storage string
        _historyStorageName = 'treeHistory__'+treeID
        _currentIndexStorageName = 'treeHistoryIndex__'+treeID

        // if localStorage is empty, create it
        if(localStorage.getItem(_historyStorageName) === null) {
            // sets a blank history and index
            this.clearHistory();
            this.emit('historyCreate', 'historyCreate', {})
        } else {
            // the history has been created before, so emit a reload
            this.emit('historyReload', 'historyReload', {})
        }

        // set from localStorage
        this.setHistory(JSON.parse(localStorage.getItem(_historyStorageName)))
        // set currentIndex from localStorage
        this.setCurrentIndex(JSON.parse(localStorage.getItem(_currentIndexStorageName)))
    }

    this.setHistory = function(history) {
        // TODO: different checks to make sure it's legit, like
        // don't add the same state twice.
        _saveHistory(history)
        // notify observers
        this.notifyObservers('historyUpdate', history)
        return
    }

    this.setCurrentIndex = function(index) {
        let history = this.getHistory()
        // Check that the index exists
        if(index !== null && history[index] === undefined) {
            console.error('Index not found in History.')
            // Should we set the current index to null here?
            return false
        }

        // don't worry about matching that the state exists. Maybe someone wants to set the current index to the last one in the series. Who knows?
        _saveCurrentIndex(index)
        this.notifyObservers('historyIndexUpdate', index)
        return
    }

    this.setView = function(container) {

    }

    // if a Tree was passed, build the History now
    if(options.Tree) {
        this.build(options.Tree)
    }
}


TreeHistory.prototype = {
    constructor: TreeHistory,

    build: function(Tree) {
        let history;
        this.setTree(Tree)
        this.init()
        // check the validity of the history, if it's not cool, clear history and run again
        history = this.getHistory()
        // if the first and second items aren't valid, reset it
        if(history[0].type !== 'intro' || history[1].type !== 'overview' ) {
            this.clearHistory()
        }
        this.forceCurrentState()
        // let everyone know the treeHistory is ready
        // emit that we've finished building the History
        this.emit('ready', 'historyReady', this)
    },

    addHistory: function(state) {
        // TODO: Validate state. Should be a function on the Tree
        // check whitelist for state?

        let history = this.getHistory();
        // add the state to the history
        history.push(state)
        // save it
        this.setHistory(history)
    },

    deleteHistoryAfter: function(index) {
        let history;
        // Don't let them delete the current state
        if(index === this.getCurrentIndex()) {
            console.error('Cannot delete current state.');
            return false;
        }

        // don't allow them to delete history before the current index
        if(index < this.getCurrentIndex) {
            console.error('Cannot delete states before the current state.');
            return false;
        }

        history = this.getHistory()
        // ok, delete away!
        // delete all history after the passed index
        // splice returns the delete array elements
        history.splice(index)
        this.setHistory(history)
    },

    getCurrentHistoryState: function() {
        let history,
            currentIndex;

        history = this.getHistory()
        currentIndex = this.getCurrentIndex()

        return history[currentIndex]
    },

    /**
    * Let our Tree know about the state we want to change to.
    */
    emit: function(action, item, data) {
        let Tree = this.getTree()
        switch(action) {
            case 'ready':
                Tree.message(item, data);
                break
            case 'update':
                Tree.update(item, data);
                break
            case 'historyCreate':
                Tree.message(item, data);
                break
            case 'historyReload':
                Tree.message(item, data);
                break
        }
    },

    /**
    * Get messages from observers
    */
    message: function(action, item, data) {
        this.emit(action, item, data)
    },

    // tell the parent tree to update to our current state
    forceCurrentState: function() {
        let currentIndex,
            history,
            data;

        currentIndex = this.getCurrentIndex();
        history = this.getHistory()

        if(this.currentIndex !== null && history[currentIndex] !== undefined) {
            data = Object.assign(history[currentIndex], {updatedBy: 'forceCurrentState', observer: 'TreeHistory'})
            this.emit('update', 'state', data)
        }

    },

    /**
    * Listen to parent Tree's emitted actions and handle accordingly
    */
    on: function(action, data) {
        switch(action) {
            case 'ready':
                // data will be the tree itself
                this.build(data)
                break
            case 'update':
                this.update(data)
                break
            case 'viewReady':
                // build the view
                // this.setView(data)
                // get the container
                let treeView = data
                let cWindow = treeView.getContentWindow()
                let historyView = new TreeHistoryView({
                    TreeHistory: this,
                    contentWindow: cWindow,
                    cssPriority: treeView.getCSSPriority()
                })
                // add this to the observers
                this.addObserver(historyView)
                break
            case 'restart':
                // delete the history
                this.clearHistory()
                break
            case 'start':
                // delete the history
                this.clearHistory()
                break
        }

        // notify observers of these changes
        this.notifyObservers(action, data)
    },

    // updates the history state
    update: function(states) {
        let newState,
            oldState,
            history,
            findNewStateIndex,
            findOldStateIndex,
            stateToAdd,
            Tree,
            currentHistoryState;

        // data contains old state and new state
        newState = states.newState
        oldState = states.oldState
        history = this.getHistory()
        currentHistoryState = this.getCurrentHistoryState()

        // check if we're resuming where we left off. ie, the updated state will match where we're at in the state history
        if(currentHistoryState !== undefined && newState.type === currentHistoryState.type && newState.id === currentHistoryState.id) {
            // do nothing! we're good
            return;
        }

        Tree = this.getTree()
        // try to find the new state in our history
        findNewStateIndex = this.getHistoryItemIndex(newState)

        // try to find the old state in our history
        findOldStateIndex = this.getHistoryItemIndex(oldState)
        // this.getIndexBy(history, 'id', oldState.id)

        // If we can find the new state index in our history,
        // then we don't want to ADD it to the history, we just want to
        // change our currentIndex to match where they are.
        // EX. Someone clicked the "back" or "forward" buttons.
        // They're not adding history, they're just changing where they are
        if(findNewStateIndex !== undefined) {
            // set the currentIndex accordingly
            this.setCurrentIndex(findNewStateIndex);
        }

        // try to find the previous state. is it the last one in the
        // current state tree?
        // if not, delete any history after the previous state.
        // They've gone rogue by going back in history and
        // then chose a new path
        // unless we're going from Start to the First Question. We want to keep the overview in there.
        else if(findOldStateIndex !== undefined && findOldStateIndex !== history.length - 1) {
            // delete anything after this point, because they've changed their state history
            // we don't want to delete one by one because:
            // 1. we won't allow them to do that
            // 2. it'll be a lot slower to delete one by one
            // make sure we're not trying to delete the intro or tree states from the history
            if(oldState.type !== 'intro' && oldState.type !== 'overview') {
                this.deleteHistoryAfter(findOldStateIndex + 1)
            }

            // add our new history
            // set it as our var to add
            stateToAdd = newState;
        } else {
            // welp, they're just going forwards.
            // Nothing to do but add the state!
            stateToAdd = newState;
        }

        // see if there's anything to add
        if(typeof stateToAdd === 'object' && stateToAdd !== undefined) {
            this.addHistory(stateToAdd)
            // set the new current index
            this.setCurrentIndex(this.getHistory().length-1)
        }
    },

    setObservers: function(observers) {
        for(let i = 0; i < observers.length; i++) {
            this.addObserver(observers[i])
        }
        return this.observers
    },

    addObserver: function(observer) {
        // no need to validate. anyone can listen
        // we do need to check to make sure the observer hasn't already
        // been added
        this.observers.push(observer)
    },

    getObservers: function() {
        return this.observers
    },

    notifyObservers: function(action, data) {
        for(let i = 0; i < this.observers.length; i++) {
            // async emit
            setTimeout(() => {
                this.observers[i].on(action, data)
            }, 0)
        }
    },

    // finds an item in the history object.
    getHistoryItemIndex(state) {
        let history,
            index;

        history = this.getHistory()
        // check for tree or intro here because there will only ever be one in the history
        // and their ID is set as the tree_id which matches each other
        if(state.type === 'overview' || state.type === 'intro') {
            index = this.getIndexBy(history, 'type', state.type)
        } else {
            index = this.getIndexBy(history, 'id', state.id)
        }

        return index;
    },

    /**
    * Powers most all of the retrieval of data from the tree
    * Searches an array for a key that equals a certain value
    *
    * @param objArray (ARRAY of OBJECTS)
    * @param name (STRING) of the key you're wanting to find the matching value of
    * @param value (MIXED) the value you want to find a match for
    * @return INT of the index that matches or UNDEFINED if not found
    */
    getIndexBy: function(objArray, name, value){
        for (let i = 0; i < objArray.length; i++) {
            if (objArray[i][name] == value) {
                return i;
            }
        }
        return undefined;
    }
}
