function TreeView(options) {
    var _Tree,
        _container,
        _treeEl,
        _contentWindow,
        _contentPane,
        _activeEl,
        _cssPriority;

    if(typeof options.container !== 'object') {
        console.error('Tree container must be a valid object. Try `container: document.getElementById(your-id)`.')
        return false
    }

    // getters
    this.getContainer = function() { return _container}
    this.getTree = function() { return _Tree}
    this.getTreeEl = function() { return _treeEl}
    this.getActiveEl = function() { return _activeEl}
    this.getContentWindow = function() { return _contentWindow}
    this.getContentPane = function() { return _contentPane}
    this.getCSSPriority = function() { return _cssPriority}

    // setters
    this.setTree = function(Tree) {
        // only let it be set once
        if(_Tree === undefined) {
            _Tree = Tree
        }
        return _Tree
    }

    // setters
    this.setTreeEl = function() {
        // only let it be set once
        if(_treeEl === undefined) {
            // this will be the tree rendered by handlebars
            _treeEl = _container.firstElementChild
        }
        return _treeEl
    }

    this.setContentWindow = function() {
        // only let it be set once
        if(_contentWindow === undefined) {
            _contentWindow = document.getElementById('cme-tree__content-window--'+_Tree.getTreeID())
        }
        return _contentWindow
    }

    this.setContentPane = function() {
        // only let it be set once
        if(_contentPane === undefined) {
            _contentPane =  _contentWindow.firstElementChild
        }
        return _contentPane
    }

    this.setCSSPriority = function(cssPriority) {
        // only let it be set once
        if(_cssPriority === undefined && (cssPriority === 'important' || cssPriority === '!important')) {
            _cssPriority =  '!important'
        } else {
            _cssPriority = ''
        }
        return _cssPriority
    }

    // Pass a state for it to set to be active
    this.setActiveEl = function(state) {
        let el,
            elId;

        if(state.type === 'overview') {
            elId = 'cme-tree--'+state.id
        } else {
            elId = 'cme-tree__el--'+state.id
        }
        // check if classname matches, if we're even going to change anything
        if(_activeEl !== undefined && _activeEl.id === elId) {
            // do nothing
            return _activeEl;
        }
        // they're trying to set a new state, so let's see if we can
        el = document.getElementById(elId)
        if(typeof el !== 'object') {
            console.error('Could not set active element for state type '+state.type+ ' with state id '+state.id)
            return false
        }

        // valid (could be more checks, but this is good enough)
        // set it
        _activeEl = el
        return _activeEl
    }

    /***********************
    ******** INIT  *********
    ***********************/
    // set an active className
    this.activeClassName = 'is-active'
    // how long animation classes are applied for until removed
    this.animationLength = 500
    // not ideal to pass this, but it's SOOO much faster performance wise
    this.scaledSize = 0.65
    // create a stylesheet we can add rules to
    this.stylesheet = this.createStylesheet()
    // get the window width for resize checks
    this.windowWidth = window.innerWidth
    // set the el
    _container = options.container;
    // set the priority
    this.setCSSPriority(options.cssPriority);

    // attach event listeners to the tree element with
    // bound `this` so we get our reference to this element
    _container.addEventListener("click", this.click.bind(this));
    _container.addEventListener("keydown", this.keydown.bind(this));
    // add a resize timeout so we know if one is already firing
    this.resizeTimeout = null
    window.addEventListener("resize", this.resize.bind(this));

    // if a Tree was passed, build the view now
    if(options.Tree) {
        this.build(options.Tree)
    }



}

TreeView.prototype = {
    constructor: TreeView,

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
                this.updateState(data)
                break
            // used by PostMessage to get the tree height to pass to the iframe
            case 'getTreeHeight':
                this.emit('treeHeight', 'treeHeight', {treeHeight: this.getTreeHeight()})
        }
    },

    build: function(Tree) {

        this.setTree(Tree)
        this.render(Tree.getData())

        // set the tree wrap
        this.setContentWindow()
        // set the questions wrap
        this.setContentPane()
        // let everyone know the tree view is ready
        // emit that we've finished render
        this.emit('ready', 'viewReady', this)
        // set the current state in the view
        let init = true
        // calculate size
        this.setState(Tree.getState(), init)
        this.updateViewHeight(Tree.getState())

        // if we don't have any groups calculated, go ahead and calculate to store the values
        this.checkForGroupsCalc()
    },

    render: function(data) {
        // render the tree
        var template = window.TreeTemplates.tree
        var treeHTML = template(data)
        // set the HTML into the passed container
        this.getContainer().innerHTML = treeHTML
        // set the Tree El
        this.setTreeEl()
        // bind question data
        this.bindAllData()
    },

    getGroups: function() {
        let treeEl,
            groups;

        treeEl = this.getTreeEl()
        return treeEl.getElementsByClassName('cme-tree__group')
    },

    getQuestions: function() {
        let treeEl;

        treeEl = this.getTreeEl()
        return treeEl.getElementsByClassName('cme-tree__question')
    },

    getQuestion: function(id) {
        return this.getDestination(id)
    },

    getEnds: function() {
        let treeEl;

        treeEl = this.getTreeEl()
        return treeEl.getElementsByClassName('cme-tree__end')
    },

    getEnd: function(id) {
        return this.getDestination(id)
    },

    getDestination: function(destination_id) {
        return document.getElementById('cme-tree__el--'+destination_id)
    },

    getOptions: function(question) {
        return question.getElementsByClassName('cme-tree__option-link')
    },

    getDestinationIcon: function(option_id) {
        return document.getElementById('cme-tree__destination-icon--'+option_id)
    },

    getStylesheet: function() {
        return this.stylesheet
    },

    // from https://davidwalsh.name/add-rules-stylesheets
    createStylesheet: function() {
        let style

        style = document.createElement('style')

    	// WebKit hack :(
    	style.appendChild(document.createTextNode(""))

    	// Add the <style> element to the page
    	document.head.appendChild(style)

    	return style.sheet
    },

    getTreeHeight: function() {
        return this.getAbsoluteBoundingRect(this.getContainer()).height
    },

    /**
    * Used when a state is already set and we need to change it
    */
    updateState: function(data) {
        let oldState,
            newState,
            oldActiveEl,
            newStateSuccess;


        oldState = data.oldState
        newState = data.newState
        oldActiveEl = this.getActiveEl()


        // removes container state class
        if(oldState.type !== newState.type) {
            this.removeContainerState(oldState)
        }
        if(oldState.id !== newState.id) {
            // get active element
            oldActiveEl.classList.remove(this.activeClassName)
            // animate out
            oldActiveEl.classList.add('cme-tree__'+oldState.type+'--animate-out')
            setTimeout(()=>{
                 oldActiveEl.classList.remove('cme-tree__'+oldState.type+'--animate-out')
            }, this.animationLength)
        }

        // activate new state
        // data.newState.id
        newStateSuccess = this.setState(data.newState)

        // delay the updateViewHeight if we're switching to/from the 'overview' since there's a lot that happens height/transform wise in that time
        if(oldState.type === 'overview' || newState.type === 'overview') {
            setTimeout(()=>{
                this.updateViewHeight(newState)
            }, this.animationLength)
        } else {
            // don't worry about delaying
            this.updateViewHeight(newState)
        }

        // update focusable elements
        this.updateFocusable(oldState, newState)

        // revert back to old state
        if(newStateSuccess === false) {
            this.setState(oldState)
            this.updateViewHeight(oldState)
        }
    },

    setState: function(state, init) {
        let activeEl;
        this.addContainerState(state)
        // set active element
        activeEl = this.setActiveEl(state)

        // if set active fails... what to do?
        if(activeEl === false) {
            return false;
        }
        // validated, so set the new class!
        activeEl.classList.add(this.activeClassName)
        // we don't want to add focus on init

        if(init !== true) {
            // focus it
            // don't add focus until after the event has finished animating.
            // This prevents a big jump on the page when focus an element before it arrives into view
            // also it's a big performance hit (30ms) when focusing an element outside of the view. it makes the browser think it needs to repaint everything.
            window.setTimeout(()=>{
                activeEl.focus()
            }, this.animationLength)
        } else {
            // if it's the first ever load, we need to remove focus
            if(state.type !== 'overview') {
                // if init is true && state= doesn't equal tree/overview, then remove all focus as a starting point
                this.removeAllFocusable()
            }
        }
        return true;
    },

    calculateQuestionsSize: function() {
        let questions,
            bounds;
        questions = this.getQuestions()
        for(let i =0; i < questions.length; i++) {
            questions[i].data.bounds = {
                offsetHeight: questions[i].offsetHeight,
                offsetTop: questions[i].offsetTop
            }
        }
    },

    calculateEndsSize: function() {
        let ends,
            bounds;
        ends = this.getEnds()
        for(let i =0; i < ends.length; i++) {
            ends[i].data.bounds = {
                offsetHeight: ends[i].offsetHeight,
                offsetTop: ends[i].offsetTop
            }
        }
    },

    checkEndPositionAccuracy: function() {
        let ends,
            end,
            accurate = false;

        // get the last end
        ends = this.getEnds()
        end = ends[ends.length - 1]

        // check if the end positions match the stored data
        if(end.data.bounds !== undefined && end.data.bounds.offsetHeight === end.offsetHeight && end.data.bounds.offsetTop === end.offsetTop) {
            accurate = true
        }


        return accurate;
    },

    updateViewHeight: function(state) {
        let activeEl,
            cPanel,
            cWindow,
            cWindowHeight,
            cPanelTransform,
            questionOffsetTop,
            groupsHeight;

        activeEl = this.getActiveEl()
        // if we're on a question, set the transform origin on the wrapper
        cPanel = this.getContentPane()
        cWindow = this.getContentWindow()

        if(state.type === 'question' || state.type === 'end') {

            // content window is what you can see and the pane is the full height element with transform origin applied on it. Think of a big piece of paper (the panel) and it's covered up except for a small window that you're looking through

            // if we change the margins based on a state change here, it'll mess up
            // the calculation on offsetTop. If we're going to do that, we need to
            // delay the margin change until after the animation has completed

            // Also, offsetTop only works to the next RELATIVELY positioned element, so the activeEl container (cPanel) must be set position relative
            // check to make sure we have sizes
            if(activeEl.data.bounds === undefined) {
                // calculate both for reference
                this.calculateQuestionsSize()
                this.calculateEndsSize()
            }

            questionOffsetTop = -activeEl.data.bounds.offsetTop
            cPanelTransform = 'translate3d(0,'+ questionOffsetTop+'px,0)'
            // set window scroll position to 0 (helps on mobile to center the question correctly)
            // cWindow.scrollTop = 0
            // set a height
            cWindowHeight = activeEl.data.bounds.offsetHeight

            // do an async check to see if we need to recalculate the positions

            // This doesn't seem necessary since I had tried to use this to fix
            setTimeout(() =>{
                console.log('resize check')

                // check the position of the first end state
                if(this.checkEndPositionAccuracy() !== true) {
                    // pass true to force the resize
                    this.resize(true)
                }
            }, 200)

            setTimeout(()=> {
                // this is generally wrong on iOS safari when transitioning a long distance
                // like from 1st question to an end state.
                // it will be 0 on desktop browsers though.
                // console.log('cwindowScrollTop', cWindow.scrollTop)
                // in order to fix this, set it to scrollTop = 0 at the end of the transition time
                cWindow.scrollTop = 0
            }, this.animationLength  + 100)
        }

        else if(state.type === 'intro') {
            // kicks off the process for calculating necesary group data if necessary
            this.checkForGroupsCalc()
        }
        // if the state type is tree, set a height on the window and distribute the groups accordingly
        else if(state.type === 'overview') {
            // kicks off the process for calculating necesary group data if necessary
            this.checkForGroupsCalc()
            // get the groups height
            groupsHeight = this.getGroupsHeight()

            this.displayArrowDirection()
            // make sure the height of the groups isn't taller than the cWindowHeight
            cWindowHeight = cPanel.getBoundingClientRect().height

            if(cWindowHeight < groupsHeight) {
                cWindowHeight = groupsHeight
            }

            // reset the transform origin
            cPanelTransform = ''
        } else {
            cPanelTransform = ''
        }

        // set the transforms
        // cWindow.style.height = cWindowHeight+'px'
        // cPanel.style.transform = cPanelTransform
        cWindow.setAttribute('style', 'height: '+cWindowHeight+'px'+this.getCSSPriority()+';');
        this.setTransform(cPanel, cPanelTransform)
        // emit to let everyone know we finished updating the height
        this.emit('viewChange', 'viewHeightUpdate', {cWindowHeight: cWindowHeight, questionOffsetTop: questionOffsetTop })
    },

    addContainerState: function(state) {
        // set the state type on the container
        let treeEl = this.getTreeEl()
        let classes = treeEl.classList;
        // if the class isn't already there, add it
        if(!classes.contains('cme-tree__state--'+state.type)) {
            classes.add('cme-tree__state--'+state.type)
        }
    },

    removeContainerState: function(state) {
        // set the state type on the container
        let treeEl = this.getTreeEl()
        treeEl.classList.remove('cme-tree__state--'+state.type)

        // add animation classes
        treeEl.classList.add('cme-tree__state--animate-out--'+state.type)
        window.setTimeout(()=>{ treeEl.classList.remove('cme-tree__state--animate-out--'+state.type) }, this.animationLength)
    },

    keydown: function(event) {
        // check to see if it's a spacebar or enter keypress
        // 13 = 'Enter'
        // 32 = 'Space'
        // 9 = 'Tab'
        if(event.keyCode === 13 || event.keyCode === 32) {
            // call the click
            this.click(event, {updatedBy: 'keypress', observer: 'TreeView'})
        }
    },

    updateFocusable: function(oldState, newState) {
        let oldFocusedEl,
            newFocusedEl;

        // intro & old tree get everything removed
        if(newState.type === 'intro' || oldState.type === 'overview') {
            // remove focus from everything
            this.removeAllFocusable()
        }

        // questions & ends
        if(oldState.type === 'question' || oldState.type === 'end') {
            // set tabindex -1 on focusable options

            oldFocusedEl = this.getDestination(oldState.id)
            this.removeFocusable(oldFocusedEl)
        }

        // we need to add all focusable if in tree state
        if(newState.type === 'overview') {
            // remove focus from everything
            this.addAllFocusable()
        }

        if(newState.type === 'question' || newState.type === 'end') {
            // set tabindex -1 on focusable options
            newFocusedEl = this.getDestination(newState.id)
            this.addFocusable(newFocusedEl)
        }

    },

    // parentEl
    // sets them all 'a' els to tabindex = -1
    removeFocusable: function(parentEl) {
        let focusable;

        focusable = parentEl.querySelectorAll('a')
        for(let i = 0; i < focusable.length; i++) {
            focusable[i].tabIndex = -1
        }
    },

    // parentEl
    // sets them all 'a' els to tabindex = -1
    addFocusable: function(parentEl) {
        let focusable;
        focusable = parentEl.querySelectorAll('a')
        for(let i = 0; i < focusable.length; i++) {
            focusable[i].tabIndex = 0
        }
    },

    addAllFocusable: function() {
        let focusable;

        // combine them into one array
        focusable = document.querySelectorAll('.cme-tree__question, .cme-tree__end');

        for(let i = 0; i < focusable.length; i++) {
            this.addFocusable(focusable[i])
        }
    },

    removeAllFocusable: function() {
        let focusable;
        // combine them into one array
        focusable = document.querySelectorAll('.cme-tree__question, .cme-tree__end');
        for(let i = 0; i < focusable.length; i++) {
            this.removeFocusable(focusable[i])
        }
    },


    click: function(event, extraData) {
        let el,
            Tree,
            state;
        if(extraData === undefined) {
            extraData = {updatedBy: 'click', observer: 'TreeView'}
        }
        el = event.target;
        // check if it's a click on the parent tree (which we don't care about)
        if (el !== event.currentTarget) {

            // we're not on an A, but try to find one
            if(el.nodeName !== 'A') {
                // try to find one
                el = this.findAncestor(el, 'A')
            }
            // now continue on with checking if we found one or not.
            if(el.nodeName === 'A' && el.data !== undefined) {
                // check for el.data
                // if we're in the overview state, don't switch the state (unless they click the start button), just go to that question on the page
                if(this.getTree().getState().type !== 'overview' || (this.getTree().getState().type === 'overview' && el.data.type === 'start') ) {
                    event.preventDefault()
                    this.emit('update', 'state', Object.assign(el.data, extraData))
                } else {
                    // in tree state
                    event.preventDefault()
                    // focus that question/end state to show them where it is
                    let destinationEl = this.getDestination(el.data.destination_id)
                    destinationEl.focus()
                    // emit that it happened to the other observers (like TreeInteraction so we can save the interaction)
                    this.emit('overviewOptionInteraction', 'overviewOptionInteraction', el.data)
                }
            }

            // They're clicking on a question. Don't add focus
            else if(el.nodeName === 'SECTION') {
                event.preventDefault()
                // if it's a click on a question, it will have recieved focus, so we need to unset the focus. Move it to the previously focused element?
                /*Tree = this.getTree()
                state = Tree.getState();
                // make sure we're not curently on this question
                if((el.data.type === 'question' && state.id !== el.data.question_id)  || state.type !== 'question' ) {
                    this.emit('update', 'state', el.data)
                }*/

            }
        }
        event.stopPropagation()
    },

    // pass forceResize = true to skip any validation
    resize: function(forceResize) {
        // iOS fires a resize event when you switch scroll direction vertically (like, scrolling up and then back down) when the url address bar expands/retracts. We don't actually need to do this costly resize on each of those, so we'll do our questionAccuracty check.
        if(forceResize !== true) {
            // check the value of our stored window width  vs the new window width
            if(this.windowWidth === window.innerWidth) {
                // console.log('window is still the same width')
                // they match, so skip this resize.
                // Remember, you can bypass this check by passing forceResize = true to resize
                // this.resize(true)
                return false;
            }
        }


        // recalculate heights on resize
        // debounce it, kinda, by waiting 100ms until they're done so we don't fire this constantly
        this.resizeTimeout = null;
        if ( !this.resizeTimeout ) {
            this.resizeTimeout = setTimeout(()=>{
                // update the question and end sizes
                this.calculateQuestionsSize()
                this.calculateEndsSize()
                // update the group sizes
                this.calculateGroups()
                // update the heights
                this.updateViewHeight(this.getTree().getState())
                // update the windowWidth var to match what we just set it to
                this.windowWidth = window.innerWidth
            }, 450);
        }
    },

    /**
    * Let our Tree know about thangs.
    */
    emit: function(action, item, data) {
        let Tree = this.getTree()
        switch(action) {
            case 'update':
                // this is usually Tree.update('state', dataAboutNewState)
                Tree.update(item, data);
                break
            case 'ready':
                // tell the Tree to let all the other observers know that the view is ready
                Tree.message(item, data)
                break
            case 'viewChange':
                Tree.message(item, data)
                break
            case 'treeHeight':
                Tree.message(item, data)
                break
            case 'overviewOptionInteraction':
                // item = overviewOptionInteraction
                // data = option_id clicked and resulting destination data
                Tree.message(item, data)
                break
        }

    },

    /**
    * Bind tree data to the element for easy access to data when we need it
    */
    bindAllData: function() {
        let Tree = this.getTree();
        let elTypes = ['question', 'start', 'end', 'group']
        // bind a quick empty object for our treeEl so we can add data to it as needed
        let treeEl = this.getTreeEl()
        if(!treeEl.hasOwnProperty('data')) {
            treeEl.data = {}
        }
        // loop through the data and find the corresponding elements
        for(let i = 0; i < elTypes.length; i++) {
            // get the data: ex {question_id: 2, order: 2, etc}
            let elData = Tree.getDataByType(elTypes[i])
            for(let j = 0; j < elData.length; j++) {
                // get the id, ex. the id value '2'
                // this is like saying: getDataByType('question').question_id
                let id = elData[j][elTypes[i]+'_id']
                // find the element in the DOM
                let el = document.getElementById('cme-tree__el--'+id)

                // bind the data
                this.bindDOMData(elData[j], el, elTypes[i])

                // see if we're working with a question or end
                switch(elTypes[i]) {
                    case 'question':
                        let options = elData[j].options
                        // loop through the options
                        for(let k = 0; k < options.length; k++) {
                            // get option el
                            let optionEl =  document.getElementById('cme-tree__el--'+options[k].option_id)
                            // bind the data
                            this.bindDOMData(options[k], optionEl, 'option')
                        }
                        break

                    case 'end':
                        // assign data to restart button
                        // restart button
                        var restartEl = document.getElementById('cme-tree__restart--'+id)
                        this.bindDOMData(elData[j], restartEl, 'restart')
                        // go to overview button
                        var overviewEl = document.getElementById('cme-tree__overview--'+id)
                        this.bindDOMData(elData[j], overviewEl, 'overview')
                        break
                }
            }
        }
    },


    bindDOMData: function(data, element, type) {
        // we can only add this once, not overwrite existing ones
        if(element.data === undefined) {
            // clone the data so we're not giving direct access to the _data attribute
            let clonedObj;
            // building the cloned data manually so:
            // 1. it's soooo much faster. Like, exponentionally as the tree grows
            // 2. we're not recording a bunch of data we don't need
            //    (like "content", "title", etc)
            // 3. We can add data that we do need (like "type")
            switch(type) {
                case 'start':
                    clonedObj = {
                        start_id: data.start_id,
                        type: 'start',
                    }
                    break

                case 'group':
                    clonedObj = {
                        group_id: data.group_id,
                        type: 'group',
                        order: data.order
                    }
                    break

                case 'question':
                    clonedObj = {
                        question_id: data.question_id,
                        type: 'question',
                        order: data.order,
                        group_id: data.group_id
                    }
                    clonedObj.options = []
                    // add options
                    for(let i = 0; i < data.options.length; i++) {
                        clonedObj.options.push(data.options[i].option_id)
                    }
                    break

                case 'option':
                    clonedObj = {
                        option_id: data.option_id,
                        type: 'option',
                        order: data.order,
                        question_id: data.question_id,
                        destination_id: data.destination_id,
                        destination_type: data.destination_type,
                    }
                    break

                case 'end':
                    clonedObj = {
                        end_id: data.end_id,
                        type: 'end',
                        order: data.order
                    }
                    break

                case 'restart':
                    clonedObj = {
                        restart_id: data.end_id,
                        type: 'restart',
                    }
                    break
                case 'overview':
                    clonedObj = {
                        overview_id: data.end_id,
                        type: 'overview',
                    }
                    break
            }
            // dynamically building the structure was quite slow, so we're manually doing it for speed on this kinda expensive operation
            // bind the data to the DOM
            element.data = clonedObj
            return element.data
        } else {
            // can't overwrite data, so return false
            return false
        }
    },

    /*
    @method getAbsoluteBoundingRect
    @param {HTMLElement} el HTML element.
    @return {Object} Absolute bounding rect for _el_.
    */
    getAbsoluteBoundingRect: function(el) {
        var doc  = document,
            win  = window,
            body = doc.body,

            // pageXOffset and pageYOffset work everywhere except IE <9.
            offsetX = win.pageXOffset !== undefined ? win.pageXOffset :
                (doc.documentElement || body.parentNode || body).scrollLeft,
            offsetY = win.pageYOffset !== undefined ? win.pageYOffset :
                (doc.documentElement || body.parentNode || body).scrollTop,

            rect = el.getBoundingClientRect();

        if (el !== body) {
            var parent = el.parentNode;

            // The element's rect will be affected by the scroll positions of
            // *all* of its scrollable parents, not just the window, so we have
            // to walk up the tree and collect every scroll offset. Good times.
            while (parent !== body) {
                offsetX += parent.scrollLeft;
                offsetY += parent.scrollTop;
                parent   = parent.parentNode;
            }
        }

        return {
            bottom: rect.bottom + offsetY,
            height: rect.height,
            left  : rect.left + offsetX,
            right : rect.right + offsetX,
            top   : rect.top + offsetY,
            width : rect.width
        };
    },

    // calculate bind the group sizes to the DOM
    calculateGroups: function() {
        let groups,
            groupsBound;

            // get the groups
            groups = this.getGroups()

            for(let i = 0; i < groups.length; i++) {
                groups[i].data.size = {
                    width: groups[i].offsetWidth,
                    height: groups[i].offsetHeight
                }
            }

            // also calculate groupsSizes to get set on treeEl
            this.calculateGroupsSize()
            // creates the styles we need
            this.addGroupStyles()
    },

    // write group transforms to a dynamic stylesheet to improve perf.
    // rebuilds on resize
    addGroupStyles: function() {
        let groups,
            groupStyles,
            groupsOffsetLeft,
            treeEl,
            cssPriority;

        // get the groups
        groups = this.getGroups()
        // get the treeEl for our groupsOffsetLeft data
        treeEl = this.getTreeEl()
        groupsOffsetLeft = treeEl.data.groupsOffsetLeft
        cssPriority = this.getCSSPriority()

        for(let i = 0; i < groups.length; i++) {
            this.addStylesheetRule('.cme-tree__state--overview #'+groups[i].id+', .cme-tree__state--intro #'+groups[i].id, [['transform', 'translate3d('+groupsOffsetLeft+'px,'+ groups[i].data.offsetTop+'px, 0)'+cssPriority+'']])
        }
    },


    // TODO: Let there be more than one rule passed at a time
    // rules = array of arrays where those arrays are properties [key, value]
    addStylesheetRule: function(selector, rules) {
        let styles,
            insertAt;
        insertAt = null
        styles = this.getStylesheet()
        // check if the rule exists
        for(let i = 0; i < styles.cssRules.length; i++) {
            // check if this selector already exists
            if(styles.cssRules[i].selectorText === selector) {
                insertAt = i
                break
            }
        }

        // If it couldn't be found, create the rule
        if(insertAt === null) {
            // it doesn't exist, so create it
            // Insert CSS Rule
            insertAt = styles.cssRules.length
            // add the first rule as a placeholder
            styles.insertRule(selector + '{' + rules[0][0] + ': '+rules[0][1]+'}', insertAt);
        }

        // now add all the rules
        for(let i = 0; i < rules.length; i++) {
            styles.cssRules[insertAt].style[rules[i][0]] = rules[i][1]
        }




  },

    // check if we need to calclate the groups
    checkForGroupsCalc: function() {
        let groups = this.getGroups()

        // first time it gets called, calculate heights
        if(groups[0].data.size === undefined) {
            this.calculateGroups()
        }
    },

    calculateGroupsSize: function() {
        let groups,
            groupSize,
            groupsOffsetLeft,
            groupsHeight,
            groupsWidth,
            treeEl;

        groups = this.getGroups()
        groupsHeight = 0
        // batch style changes for faster painting
        for(let i = 0; i < groups.length; i++) {
            groupSize = groups[i].data.size

            if(i === 0) {
                groupsWidth = groupSize.width * this.scaledSize
                if(groupsWidth < 350) {
                    groupsOffsetLeft = groupsWidth * 1.5 + 30
                } else {
                    groupsOffsetLeft = groupsWidth * 1.5 + 50
                }
            }

            // groups[i].style.transform = 'translate3d('+groupsWidth+'px, '+groupsHeight + 'px, 0)'
            // add the offsetTop for the tree state
            groups[i].data.offsetTop = groupsHeight
            // add in the height of this one
            // an extra 110 seems to be about right for spacing
            groupsHeight = groupsHeight + groupSize.height  + 110
        }

        // write the groupsOffsetLeft and groupsHeight to our tree el
        treeEl = this.getTreeEl()
        treeEl.data.groupsOffsetLeft = groupsOffsetLeft
        treeEl.data.groupsHeight = groupsHeight

        return groupsHeight
    },

    getGroupsHeight: function() {
        let treeEl,
            groupsHeight;

        treeEl = this.getTreeEl()

        // check if the key exists, if not, add it
        if(!treeEl.data.hasOwnProperty('groupsHeight')) {
            this.calculateGroupsSize()
        }

        return treeEl.data.groupsHeight
    },

    // https://gist.github.com/conorbuck/2606166
    // p1 and p2 need x and y cordinates
    // p1 = {x: 12, y: 15}
    lineAngle: function(p1, p2) {
        let degrees;
        // angle in radians
        // let angleRadians = Math.atan2(p2.y - p1.y, p2.x - p1.x);
        // angle in degrees
        degrees = Math.atan2(p2.y - p1.y, p2.x - p1.x) * 180 / Math.PI;
        if(Math.sign(degrees) === -1) {
            degrees = degrees + 360
        }
        return degrees
    },

    displayArrowDirection: function() {
        let questions,
            destination,
            destinationPosition,
            destinationCoords,
            options,
            arrow,
            arrowPosition,
            arrowAngle,
            arrowCoords,
            arrowAngleNormalized;

        questions = this.getQuestions()
        // figure out arrow directions
        for(let q = 0; q < questions.length; q++) {

            options = this.getOptions(questions[q])

            for(let o = 0; o < options.length; o++) {
                if(options[o].data.destination_type === 'question') {
                    // see if the question and option destination are in
                    // the same column.
                    destination = this.getDestination(options[o].data.destination_id)
                    if(questions[q].data.group_id === destination.data.group_id) {
                        // if so, skip it (just use the down arrow)
                        continue;
                    }
                    // ok, they're in different columns, figure out what direction it needs to go
                    arrow = this.getDestinationIcon(options[o].data.option_id)

                    arrowPosition = this.getAbsoluteBoundingRect(arrow)
                    destinationPosition = this.getAbsoluteBoundingRect(destination)

                    arrowCoords = {y: arrowPosition.top + (arrowPosition.height/2)}
                    // we want the top third of the destination
                    destinationCoords = {y: destinationPosition.top + (destinationPosition.height/2)}


                        // we're going to an attachment
                    arrowCoords.x = arrowPosition.left + (arrowPosition.width/2)
                    destinationCoords.x = destinationPosition.left + (destinationPosition.width/2)

                    arrowAngle = this.lineAngle(arrowCoords, destinationCoords)

                    // adjust the arrow angle to be between 0 and 360 and


                    // now normalize it for 0 = pointing to right
                    arrowAngleNormalized = 360-arrowAngle
                    // arrow angle is between 70 and 120
                    if(80 < arrowAngleNormalized && arrowAngleNormalized < 90) {
                        this.templateArrowUpRight(arrow)
                    }
                    else if(90 < arrowAngleNormalized && arrowAngleNormalized < 100) {
                        this.templateArrowUpLeft(arrow)
                    }
                    // arrow angle is between 0-20 or 340-360
                    else if(arrowAngleNormalized < 10 || 350 < arrowAngleNormalized) {
                        // straight to the right (since we start with a down arrow)
                        this.templateArrow(arrow, 'arrow')
                        this.setTransform(arrow, 'rotate(180deg)')
                    }
                    else if(170 < arrowAngleNormalized && arrowAngleNormalized < 190) {
                        // straight to the left (since we start with a down arrow)
                        this.templateArrow(arrow, 'arrow')
                        this.setTransform(arrow, 'rotate(-180deg)')
                    }
                    // down and to the right
                    else if(270 < arrowAngleNormalized && arrowAngleNormalized < 280) {
                        // straight to the left (since we start with a down arrow)
                        this.templateArrowDownRight(arrow)
                    }
                    else if(260 < arrowAngleNormalized && arrowAngleNormalized < 270) {
                        // straight to the left (since we start with a down arrow)
                        this.templateArrowDownLeft(arrow)
                    } else {
                        this.templateArrow(arrow, 'arrow')
                        this.setTransform(arrow, 'rotate('+arrowAngle+'deg)')
                    }

                }

            }
        }
    },

    templateArrowUpRight(svg) {
        this.templateArrow(svg, 'arrow-turn')
        this.setTransform(svg, 'rotateX(-180deg)')
    },

    templateArrowUpLeft(svg) {
        this.templateArrow(svg, 'arrow-turn')
        this.setTransform(svg, 'rotateX(180deg)')
    },

    templateArrowDownRight(svg) {
        this.templateArrow(svg, 'arrow-turn')
        this.setTransform(svg, 'rotateX(0deg)')
    },

    templateArrowDownLeft(svg) {
        this.templateArrow(svg, 'arrow-turn')
        this.setTransform(svg, 'rotateX(-180deg)')
    },

    templateArrow(svg, iconName) {
        // Use childNodes for IE Edge
        svg.childNodes[0].setAttribute('xlink:href', '#icon-'+iconName)

        return svg
    },

    findAncestor(el, theNodeName) {
        let i = 0;
        // only check a few elements so we don't go too crazy
        while (el.nodeName != theNodeName && i < 4) {
            el = el.parentElement;
            i++;
        }

        return el;

    },

    setTransform: function(element, transform) {
        element.setAttribute('style', 'transform: '+ transform + this.getCSSPriority()+';')
    }
}
