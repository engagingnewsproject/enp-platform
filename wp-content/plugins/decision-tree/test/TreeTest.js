var assert = chai.assert;
var expect = chai.expect;

treeOptions = {
        slug: 'citizen',
        container: document.getElementById('cme-tree__citizen')
};

var tree = new Tree(treeOptions);
describe('Tree', function() {

    before(function(done) {
        // set a quick timeout to make sure our tree is set-up before we test it
        setTimeout(function(){
              // complete the async before
              return done();
        }, 100);
    });

    after(function() {
        // runs after all tests in this block
    });

    beforeEach(function() {
        // runs before each test in this block

    });

    afterEach(function() {
        // runs after each test in this block

    });

    describe('getIndexBy', function() {

        it('should return the correct index of an object array', function() {

            var questions = tree.getDataByType('question');
            var lastQuestionIndex = questions.length - 1;
            var lastQuestionID = questions[lastQuestionIndex].question_id;
            var getIndexLastQuestion = tree.getIndexBy(questions, 'question_id', lastQuestionID);

            expect(lastQuestionIndex).to.equal(getIndexLastQuestion);
        });
    });
    // most of these are covered by questions, groups, starts,
    // and ends queries.
    // We're just doing some direct to make sure.
    describe('getDataByType', function() {

        it('should return all tree questions json', function() {
            var questions = tree.getDataByType('question');
            var type = typeof questions;
            expect(type).to.equal('object');
        });

        it('should return first question\'s json', function() {
            // get all of them so we can get the first id to make a valid call
            var questions = tree.getDataByType('question');
            // get the first one
            var question = tree.getDataByType('question', questions[0].question_id);

            expect(questions[0].question_id)
            .to
            .equal(question.question_id);
        });

        it('should return undefined when using an invalid id', function() {
            // get all of them so we can get the first id to make a valid call
            var questions = tree.getDataByType('question');
            // get the first one
            var question = tree.getDataByType('question', 123124);

            expect(question)
            .to
            .equal(undefined);
        });

        it('should only allow whitelisted names, not `foo`', function() {
            // get all of them so we can get the first id to make a valid call
            var foo = tree.getDataByType('foo');

            expect(foo)
            .to
            .equal(false);
        });

    });

    describe('setState', function() {

        it('should initialize as the state of "start"', function() {
            expect('start').to.equal(tree.getState());
        });

        it('should initialize as the stateID of the first start_id', function() {
            var starts = tree.getDataByType('start');
            expect(tree.getStateID()).to.equal(starts[0].start_id);
        });

        it('should set the question_id to first question\'s question_id', function() {
            var questions = tree.getDataByType('question');
            var question_id = questions[0].question_id;
            tree.setState('question', question_id);

            expect(question_id).to.equal(tree.getStateID());
        });

        it('should set the state to "question"', function() {
            var questions = tree.getDataByType('question');
            var question_id = questions[0].question_id;
            tree.setState('question', question_id);

            expect('question').to.equal(tree.getState());
        });

        it('should set the question_id to first question\'s question_id', function() {
            var questions = tree.getDataByType('question');
            var question_id = questions[0].question_id;
            tree.setState('question', question_id);

            expect(question_id).to.equal(tree.getStateID());
        });

        it('should set the state to "end"', function() {
            var ends = tree.getDataByType('end');
            var end_id = ends[0].end_id;
            tree.setState('end', end_id);

            expect('end').to.equal(tree.getState());
        });

        it('should set the end_id to first end\'s end_id', function() {
            var ends = tree.getDataByType('end');
            var end_id = ends[0].end_id;
            tree.setState('end', end_id);

            expect(end_id).to.equal(tree.getStateID());
        });

        it('should set the state to "start"', function() {
            var starts = tree.getDataByType('start');
            var start_id = starts[0].start_id;
            tree.setState('start', start_id);

            expect('start').to.equal(tree.getState());
        });

        it('should set the start_id to first start\'s start_id', function() {
            var starts = tree.getDataByType('start');
            var start_id = starts[0].start_id;
            tree.setState('start', start_id);

            expect(start_id).to.equal(tree.getStateID());
        });

        it('should not set the state to "foo"', function() {
            state = tree.setState('foo', 1);
            expect(state).to.equal(false);
        });

        it('should not set the state to an invalid question_id', function() {
            state = tree.setState('question', '12398019283102983102983102938102938');
            expect(state).to.equal(false);
        });
    });

    describe('getQuestions', function() {

        it('should return all tree questions json', function() {
            var questions = tree.getQuestions();

            var type = typeof questions;
            expect(type).to.equal('object');
        });

        it('should return first question\'s json', function() {
            // get all of them so we can get the first id to make a valid call
            var questions = tree.getQuestions();
            // get the first one
            var question = tree.getQuestions(questions[0].question_id);

            expect(questions[0].question_id)
            .to
            .equal(question.question_id);
        });

        it('should return undefined when using an invalid id', function() {
            // get all of them so we can get the first id to make a valid call
            var questions = tree.getQuestions();
            // get the first one
            var question = tree.getQuestions(123124);

            expect(question)
            .to
            .equal(undefined);
        });
    });

    describe('getGroups', function() {

        it('should return all tree groups json', function() {
            var groups = tree.getGroups();

            var type = typeof groups;
            expect(type).to.equal('object');
        });

        it('should return first group\'s json', function() {
            // get all of them so we can get the first id to make a valid call
            var groups = tree.getGroups();
            // get the first one
            var group = tree.getGroups(groups[0].group_id);

            expect(groups[0].group_id)
            .to
            .equal(group.group_id);
        });

        it('should return undefined when using an invalid id', function() {
            // get all of them so we can get the first id to make a valid call
            var groups = tree.getGroups();
            // get the first one
            var group = tree.getGroups(123124);

            expect(group)
            .to
            .equal(undefined);
        });
    });

    describe('getEnds', function() {

        it('should return all tree ends json', function() {
            var ends = tree.getEnds();

            var type = typeof ends;
            expect(type).to.equal('object');
        });

        it('should return first end\'s json', function() {
            // get all of them so we can get the first id to make a valid call
            var ends = tree.getEnds();
            // get the first one
            var end = tree.getEnds(ends[0].end_id);

            expect(ends[0].end_id)
            .to
            .equal(end.end_id);
        });

        it('should return undefined when using an invalid id', function() {
            // get all of them so we can get the first id to make a valid call
            var ends = tree.getEnds();
            // get the first one
            var end = tree.getEnds(123124);

            expect(end)
            .to
            .equal(undefined);
        });
    });

    describe('getStarts', function() {

        it('should return all tree starts json', function() {
            var starts = tree.getStarts();

            var type = typeof starts;
            expect(type).to.equal('object');
        });

        it('should return first start\'s json', function() {
            // get all of them so we can get the first id to make a valid call
            var starts = tree.getStarts();
            // get the first one
            var start = tree.getStarts(starts[0].start_id);

            expect(starts[0].start_id)
            .to
            .equal(start.start_id);
        });

        it('should return undefined when using an invalid id', function() {
            // get all of them so we can get the first id to make a valid call
            var starts = tree.getStarts();
            // get the first one
            var start = tree.getStarts(123124);

            expect(start)
            .to
            .equal(undefined);
        });
    });

});
