var assert = chai.assert;
var expect = chai.expect;

// setup a new EnpIframeQuiz
var newIframe = {
    iframe: document.getElementById('enp-quiz-iframe-1'),
    parentURL: window.location.href,
    abTestID: false,
    quizID: '1',
};

var enpIframe = new EnpIframeQuiz(newIframe);

describe('EnpIframeQuiz', function() {
    before(function() {

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

    describe('setQuizHeight', function() {

        var setQuizHeightSpy = sinon.spy(enpIframe, 'setQuizHeight');

        it('should set the iframe height to 200px', function() {
            var height = '200px';
            var newHeight = enpIframe.setQuizHeight(height);
            expect(height).to.equal(newHeight);
        });

        it('should not set the iframe to an invalid value', function() {
            var height = '200wut';
            var newHeight = enpIframe.setQuizHeight(height);
            expect(newHeight).to.equal(false);
        });

    });

    describe('setSiteName', function() {

        it('should set the site name to "Mocha Test"', function() {
            expect(enpIframe.getSiteName()).to.equal('Mocha Test');
        });

        it('should set the site name to "Wut"', function() {
            enpIframe.setSiteName('Wut');
            expect(enpIframe.getSiteName()).to.equal('Wut');
        });

        it('should set the blank site name to parentURL', function() {
            enpIframe.setSiteName('');
            expect(enpIframe.getSiteName()).to.equal(enpIframe.parentURL);
        });

        it('should set the null site name to parentURL', function() {
            enpIframe.setSiteName('');
            expect(enpIframe.getSiteName()).to.equal(enpIframe.parentURL);
        });

        it('should set the integer site name to parentURL', function() {
            enpIframe.setSiteName(0);
            expect(enpIframe.getSiteName()).to.equal(enpIframe.parentURL);
        });
    });

    var event = {
        origin: 'http://local.quiz',
        data: {
            site: 'http://local.quiz',
            ab_test_id: '0',
            quiz_id: '1',
        },
    };

    describe('saveEmbedSite', function() {
        // Save Embed Site
        var saveEmbedSiteSpy = sinon.spy(enpIframe, 'saveEmbedSite');
        var handleEmbedSiteResponseSpy = sinon.spy(enpIframe, 'handleEmbedSiteResponse');
        // Save Embed Quiz
        var saveEmbedQuizSpy = sinon.spy(enpIframe, 'saveEmbedQuiz');
        var handleEmbedQuizResponseSpy = sinon.spy(enpIframe, 'handleEmbedQuizResponse');

        before(function(done){
            // setup a valid event on the request
            event.data.action = 'saveSite';

            // make a fake event call
            enpIframe.receiveIframeMessage(event.origin, event.data);

            // delay it a half second so we can wait for the response
            setTimeout(function(){
                  // complete the async before
                  return done();
            }, 400);
        });

        it('should call saveEmbedSite', function() {
            // Chai + Sinon here.
            expect(saveEmbedSiteSpy).to.have.been.called;
            // When creating a spy or stub to wrap a function, you'll want
            // to make sure you restore the original function back at the
            // end of your test case
            saveEmbedSiteSpy.restore();
        });

        it('should call handleEmbedSiteResponse', function() {
            // Chai + Sinon here.
            expect(handleEmbedSiteResponseSpy).to.have.been.called;
            // When creating a spy or stub to wrap a function, you'll want
            // to make sure you restore the original function back at the
            // end of your test case
            handleEmbedSiteResponseSpy.restore();
        });

        it('should call saveEmbedQuiz', function() {
            // Chai + Sinon here.
            expect(saveEmbedQuizSpy).to.have.been.called;
            // When creating a spy or stub to wrap a function, you'll want
            // to make sure you restore the original function back at the
            // end of your test case
            saveEmbedQuizSpy.restore();
        });

        it('should call handleEmbedQuizResponse', function() {
            // Chai + Sinon here.
            expect(handleEmbedQuizResponseSpy).to.have.been.called;
            // When creating a spy or stub to wrap a function, you'll want
            // to make sure you restore the original function back at the
            // end of your test case
            handleEmbedQuizResponseSpy.restore();
        });

        it('should only call saveEmbedQuiz once', function() {
            expect(saveEmbedQuizSpy).calledOnce;
        });

        it('should set saveEmbedSite to true', function() {
            expect(enpIframe.getSaveEmbedSiteComplete()).to.equal(true);
        });

        it('should set saveEmbedQuiz to true', function() {
            expect(enpIframe.getSaveEmbedQuizComplete()).to.equal(true);
        });

        it('should set embedSiteID equal to 354', function() {
            expect(enpIframe.embedSiteID).to.equal('354');
        });

        it('should set embedQuizID equal to 286', function() {
            expect(enpIframe.embedQuizID).to.equal('286');
        });
    });

});
