var MenuSearchSuggestion = require('./MenuSearchSuggestion.js!jsx');

var MenuSearchBar = React.createClass({
    displayName: 'MenuSearchBar',

    propTypes: {
        tree:React.PropTypes.object,
        placeholder: React.PropTypes.string,
        handleSearch: React.PropTypes.func
    },
    getDefaultProps: function() {
        return {
            placeholder: 'Search...',
            tree : new Tree({ })
        };
    },
    componentDidMount: function() {
    },
    getInitialState: function() {
        return {
            query: '',
            suggestions: [],
            selectedIndex: -1,
            selectionMode: false,
            searchingCnt: 0
        };
    },
    handleChange: function(e)
    {
        var query = e.target.value.trim();
        this.setState({
            query: query
        });

        this.searchMenu(query);
        if(query.length == 0 ){
            this.setState({
                suggestions: [],
                searchingCnt: 0
            });
        }
    },

    searchMenu(keyword) {

        var self = this;
        var searchingCnt = this.state.searchingCnt + 1;
        var suggestions;
        var tree = this.props.tree;

        this.setState({
            searchingCnt: searchingCnt
        });

        suggestions = _.filter(tree.indexes, function(index){

            if(index.id == 0) return false;

            var title = index.node.title;
            if (!self.isMenuEntity(index.node)) {
                title = XE.Lang.trans(index.node.title);
            }
            return !!(title && title.indexOf(keyword) !== -1);
        });

        searchingCnt = this.state.searchingCnt;
        searchingCnt = searchingCnt - 1;
        self.setState(
            {
                suggestions: suggestions,
                searchingCnt: searchingCnt
            }
        );

    },

    isMenuEntity: function (node) {
        return (node.entity && (node.entity == 'menu'));
    },

    resetSearch: function () {

        var input = this.refs.input.getDOMNode();

        this.setState({
            query: '',
            selectedIndex: -1,
            selectionMode: false,
            suggestions: []
        });

        input.value = '';
        input.focus();
    },

    handleKeyDown: function handleKeyDown(e) {
        var _state = this.state;
        var query = _state.query;
        var selectedIndex = _state.selectedIndex;
        var suggestions = _state.suggestions;

        // hide suggestions menu on escape
        if (e.keyCode === Keys.ESCAPE) {
            e.preventDefault();
            this.resetSearch();
        }

        if ((e.keyCode === Keys.ENTER || e.keyCode === Keys.TAB) && query != '') {
            e.preventDefault();
            if (this.state.selectionMode) {
                this.selection(this.state.suggestions[this.state.selectedIndex]);
            }
        }

        // up arrow
        if (e.keyCode === Keys.UP_ARROW) {
            e.preventDefault();
            // last item, cycle to the top
            if (selectedIndex <= 0) {
                this.setState({
                    selectedIndex: this.state.suggestions.length - 1,
                    selectionMode: true
                });
            } else {
                this.setState({
                    selectedIndex: selectedIndex - 1,
                    selectionMode: true
                });
            }
        }

        // down arrow
        if (e.keyCode === Keys.DOWN_ARROW) {
            e.preventDefault();
            this.setState({
                selectedIndex: (this.state.selectedIndex + 1) % suggestions.length,
                selectionMode: true
            });
        }
    },

    selection(index) {
        var input = this.refs.input.getDOMNode();

        this.props.handleSearch(index.node);

        this.setState({
            query: '',
            selectionMode: false,
            selectedIndex: -1
        });

        input.value = '';
        input.focus();
    },
    handleSuggestionClick: function handleSuggestionClick(i, e) {
        this.selection(this.state.suggestions[i]);
    },
    handleSuggestionHover: function handleSuggestionHover(i, e) {
        this.setState({
            selectedIndex: i,
            selectionMode: true
        });
    },

    render: function render() {

        var query = this.state.query.trim(),
            selectedIndex = this.state.selectedIndex,
            suggestions = this.state.suggestions,
            placeholder = this.props.placeholder;

        return (
            <div className={cx({
                "form-group" : true,
                open : query.length > 0
            })}>
                <label for="inpt_srch"><span className="blind">검색</span></label>
                <input id="inpt_srch" type="text" className="inpt_txt" placeholder={placeholder} ref="input"
                       onChange={this.handleChange} onKeyDown={this.handleKeyDown}/>
                <button type="button" className="pull-right" onClick={this.resetSearch}>
                    <i className="xi-magnifier"></i><i className="xi-close"></i>
                </button>
                <MenuSearchSuggestion query={query}
                                      suggestions={suggestions}
                                      selectedIndex={selectedIndex}
                                      handleClick={this.handleSuggestionClick}
                                      handleHover={this.handleSuggestionHover}/>

            </div>
        );
    }
});


module.exports = MenuSearchBar;
