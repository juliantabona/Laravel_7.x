<template>

    <Row :gutter="12">

        <Col :span="7">

            <editorAside
                :screen="screen" :version="version"
                @showScreens="handleShowScreens()"
                @selectedScreen="handleSelectedScreen($event)"
                @showSubscriptions="handleShowSubscriptions()"
                @showGlobalVariables="handleShowGlobalVariables()"
                @showGlobalEvents="handleShowGlobalEvents()"
                @showColorScheme="handleShowColorScheme()"
                @showConditionalScreens="handleShowConditionalScreens()">
            </editorAside>

        </Col>

        <Col :span="17">

            <template v-if="activeView == 'Screens'">

                <screenEditor v-if="version.builder.screens.length"
                    :screen="screen" :version="version" :globalMarkers="globalMarkers" :key="screenId">
                </screenEditor>

                <template v-else>
                    <img src="/assets/images/Everyone.png" alt="Everyone" class="w-50">
                </template>

            </template>

            <template v-else-if="activeView == 'Global Variables'">

                <globalVariablesEditor :version="version" ></globalVariablesEditor>

            </template>

            <template v-else-if="activeView == 'Global Events'">

                <globalEventsEditor :version="version" :globalMarkers="globalMarkers"></globalEventsEditor>

            </template>

            <template v-else-if="activeView == 'Subcription Plans'">

                <subscriptionPlanEditor :version="version"></subscriptionPlanEditor>

            </template>

            <template v-else-if="activeView == 'Conditional Screens'">

                <conditionalScreensEditor :version="version"></conditionalScreensEditor>

            </template>

            <template v-else-if="activeView == 'Color Scheme'">

                <colorSchemeEditor :version="version"></colorSchemeEditor>

            </template>

        </Col>

    </Row>

</template>

<script>

    import editorAside from './aside/main.vue';
    import screenEditor from './content/screen/main.vue';
    import globalVariablesEditor from './content/global-variables/main.vue';
    import globalEventsEditor from './content/global-events/main.vue';
    import subscriptionPlanEditor from './content/subscription-plans/main.vue';
    import conditionalScreensEditor from './content/conditional-screens/main.vue';
    import colorSchemeEditor from './content/color-scheme/main.vue';

    export default {
        components: {
            editorAside, screenEditor, globalVariablesEditor, globalEventsEditor,
            subscriptionPlanEditor, conditionalScreensEditor, colorSchemeEditor
        },
        props: {
            project: {
                type: Object,
                default: null
            },
            version: {
                type: Object,
                default: null
            }
        },
        data(){
            return {
                screen: null,
                activeView: 'Screens',
                availableViews: ['Screens', 'Global Variables', 'Global Events','Subcription Plans', 'Conditional Screens', 'Color Scheme'],
            }
        },
        computed: {
            screenId(){
                return (this.screen || {}).id
            },
            globalMarkers(){

                //  If we have screens
                if( this.version.builder.screens.length ){

                    //  Get the screen marked as the first screen
                    var markers = this.version.builder.screens.map( (screen) => {
                            return screen.markers.map( (marker) => {
                                return {
                                    text: marker.name
                                }
                            })
                        }).flat(1);

                    var uniqueMarkers = [];

                    //  Only get unique markers (Remove duplicate markers if any)
                    for (let x = 0; x < markers.length; x++) {

                        //  Check if the marker has already been added
                        for (let y = 0; y < uniqueMarkers.length; y++) {
                            //  If it already has been added
                            if( markers[x].text == uniqueMarkers[y].text ){
                                //  Remove the older marker
                                uniqueMarkers.splice(y, 1);
                            }
                        }

                        //  Add the current marker
                        uniqueMarkers.push(markers[x]);

                    }

                    //  Return the unique markers
                    return uniqueMarkers;
                }

                return [];

            }
        },
        methods: {
            getFirstScreenToShow(){

                //  If we have screens
                if( this.version.builder.screens.length ){

                    //  Get the screen marked as the first screen
                    var markedScreens = this.version.builder.screens.filter( (screen) => {
                        return screen.first_display_screen == true;
                    });

                    if( markedScreens.length ){

                        //  Get the first marked screen
                        var firstDisplayScreen = markedScreens[0];

                        //  Return the first display screen
                        return firstDisplayScreen;

                    }

                    //  Otherwise get the first listed screen
                    return this.version.builder.screens[0];

                }

                return null;

            },
            handleSelectedScreen(index){

                //  If the index is set to null, then unselect any active screen
                if( index === null ){

                    //  Unselect any active screen
                    this.screen = null;

                }else{

                    //  Set the selected screen as the active screen
                    this.screen = this.version.builder.screens[index];

                }

                //  Set "Screens" as the active viewport
                this.handleShowScreens();

            },
            handleChangeView(name){
                //  If a viewport with the given name exists
                if( this.availableViews.includes( name ) ){

                    //  Set the active viewport to the given name
                    this.activeView = name;
                }
            },
            handleShowScreens(){

                //  Set "Screens" as the active viewport
                this.handleChangeView('Screens');

            },
            handleShowSubscriptions(){

                //  Set "Subcription Plans" as the active viewport
                this.handleChangeView('Subcription Plans');

            },
            handleShowGlobalVariables(){

                //  Set "Global Variables" as the active viewport
                this.handleChangeView('Global Variables');

            },
            handleShowGlobalEvents(){

                //  Set "Global Events" as the active viewport
                this.handleChangeView('Global Events');

            },
            handleShowConditionalScreens(){

                //  Set "Conditional Screens" as the active viewport
                this.handleChangeView('Conditional Screens');

            },
            handleShowColorScheme(){

                //  Set "Color Scheme" as the active viewport
                this.handleChangeView('Color Scheme');

            },
        },
        created(){

            //  Get the first screen to show
            this.screen = this.getFirstScreenToShow();
        }
    }
</script>
