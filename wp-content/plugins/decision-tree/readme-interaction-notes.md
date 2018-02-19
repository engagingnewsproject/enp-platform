CONSIDERATIONS
------------------

Can we tell...?
    - what options were interacted with in Overview state?
    - what questions were most abandoned (ie, viewed but then left it)?
    - what state a user was in when they left the tree?
    - which option was most selected?
    - what percentage of people reached an end?
    - how many people reached multiple ends?
    - what was the chance a person would end up at a certain end?





Option to Question
------------------
data:
    id: "26"
    observer: "TreeHistory"
    type: "question"
    updatedBy: "forceCurrentState"
newState:
    id: "26"
    type: "question"

int_id = 1  tree_id = 1
user_id = 'e296e28756f043679738d22ad04c8de'
int_type_id = 1 ('history')


INTERACTIONS
int_id      tree_id     user_id     int_type_id    state_type_id    state_id
=================================================================================
1           1           e296e2      1              1                1 (tree tree_id)
2           1           e296e2      3              3                2 (el_id question)
3           1           e296e2      5              3                3 (el_id question)
4           1           e296e2      5              4                6 (el_id end)
5           1           e296e2      4              2                1 (tree tree_id)



INT TYPES
int_type_id   int_type
=========================
1             load
2             reload
3             start
4             overview
5             option
6             history


ELEMENT interactions
el_int_id     int_id     el_id
==============================
1             3           6
2             4           9


STATE TYPES
state_type_id   state_type
=========================
1             intro
2             tree
3             question
4             end

STATES
state_id      int_id     el_id
==============================
1             3           6
2             4           9



/tree/1/results/

{
    loads: 1000
    reloads: 100,
    users: 900, // unique people
    interactions: 5000, (clicks/keypresses)
    interactions_per_user: 5000/900,
    intro: {
        views: 900,
        percentage: 100,
        bounces: 100,
        bounce_rate: 10%,
    }
    starts: {
        clicks: 500,
        percentage: 50,
        users: 200,
        restarts: 100// select group by users with more than one start
    },
    questions: [
        {
            question_id: 1
            views: 500, // all views
            unique_views: 200, // unique people who viewed this question
            percentage: 50,
            bounces: 50, // people who left during this question
            bounce_rate: 10%, // percentage of unique people who left during this question

            options: [{
                option_id: 2,
                selected: 200,
                selected_percentage: 40% // should these equal 100%? IE. be calculated from the total selectioons and not total views? IE - throw out people who left?
            },
            {
                option_id: 3,
                selected: 250,
                selected_percentage: 50%
            }],
        },
        {
            question_id: 5
            views: 200,
            percentage: 20,
            bounces: 10,
            bounce_rate: 5%,
            options: [{
                option_id: 6,
                selected: 40,
                selected_percentage: 20%
            },
            {
                option_id: 7,
                selected: 150,
                selected_percentage: 75%
            }],
        }
    ],
    ends: [{
        end_id: 8,
        views: 290,
        percentage: 29%,
        bounces: 145,
        bounce_rate: 50%
    },
    {
        end_id: 9,
        views: 350,
        percentage: 35%,
        bounces: 175,
        bounce_rate: 50%
    }],
    history: [{
        clicks: 8,

    }],
    overview: [{
        views: 400,
        percentage: ..%,
        unique_views: 200,
        interactions: 1000,
        questions: [{
            question_id: 1,
            options: [{
                option_id: 1,
                selected: 2,
            },
            {
                option_id: 1,
                selected: 5,
            }],
        }],
        ends: [{

        }]
    }] 
}
