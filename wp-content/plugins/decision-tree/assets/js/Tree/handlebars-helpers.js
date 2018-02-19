Handlebars.registerHelper('environment', function(options) {
    return 'has-js';
});

Handlebars.registerHelper('group_start', function(question_id, group_id, groups, options) {
    // find the group
    for(let i = 0; i < groups.length; i++) {
        if(groups[i].group_id === group_id) {
            // check if it's the first in the question order
            if(groups[i].questions[0] === question_id) {
                // pass the values we'll need in the template
                return options.fn({group_id: groups[i].group_id, group_title: groups[i].title});
            } else {
                return '';
            }
        }
    }
    return '';
});

Handlebars.registerHelper('group_end', function(question_id, group_id, groups, options) {
    // find the group
    for(let i = 0; i < groups.length; i++) {
        if(groups[i].group_id === group_id) {
            let questions = groups[i].questions;
            // check if it's the last in the question order
            if(questions[questions.length - 1] === question_id) {
                return options.fn(this);
            } else {
                return '';
            }
        }
    }
    return '';
});

Handlebars.registerHelper('el_number', function(el_order) {

    // for the arrow direction we could calculate the position on the tree-view and set an angle so the arrow points towards the destination...
    return parseInt(el_order) + 1;
});

Handlebars.registerHelper('destination', function(destination_id, destination_type, option_id, question_index, options) {
    let data,
        destination,
        destination_number,
        destination_title,
        destination_icon,
        i;
    // set data (either questions or ends most likely) from main data tree
    data = options.data.root[destination_type+'s']
    i = 0;
    if(destination_type === 'question') {
        // start it at the question_index.
        // An option will never go backwards, so we don't care
        // about the previous ones
        i = question_index;
    }

    // find the destination
    for (i; i < data.length; i++) {
        if (data[i][destination_type+'_id'] === destination_id) {
            destination = data[i];
            if(destination_type === 'question') {
                destination_number = i+ 1
                destination_title = 'Question '+ destination_number
                destination_icon = 'arrow'
            } else {
                destination_title = data[i].title
                destination_icon = ''
            }

            break
        }
    }

    // for the arrow direction we could calculate the position on the tree-view and set an angle so the arrow points towards the destination...
    return options.fn({
            destination_title: destination_title,
            destination_type: destination_type,
            destination_icon: destination_icon,
            option_id: option_id
        });
});
