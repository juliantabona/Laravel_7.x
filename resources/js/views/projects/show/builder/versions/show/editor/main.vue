<template>

    <Row :gutter="12">

        <Col :span="7">
            
            <editorAside 
                :screen="screen" :builder="builder"
                @showScreens="handleShowScreens()"
                @selectedScreen="handleSelectedScreen($event)"
                @showSubscriptions="handleShowSubscriptions()"
                @showGlobalVariables="handleShowGlobalVariables()"
                @showConditionalScreens="handleShowConditionalScreens()">
            </editorAside>

        </Col>

        <Col :span="17">
        
            <template v-if="activeView == 'Screens'">

                <screenEditor v-if="builder.screens.length" :screen="screen" :builder="builder" :key="screenId"></screenEditor>

                <template v-else>
                    <img src="/assets/images/Everyone.png" alt="Everyone" class="w-50">
                </template>

            </template>

            <template v-else-if="activeView == 'Global Variables'">
                
                <globalVariablesEditor :builder="builder"></globalVariablesEditor>

            </template>

            <template v-else-if="activeView == 'Subcription Plans'">
                
                <subscriptionPlanEditor :builder="builder"></subscriptionPlanEditor>

            </template>

            <template v-else-if="activeView == 'Conditional Screens'">
                
                <conditionalScreensEditor :builder="builder"></conditionalScreensEditor>

            </template>

        </Col>

    </Row>

</template>

<script>

    import editorAside from './aside/main.vue';
    import screenEditor from './content/screen/main.vue';
    import globalVariablesEditor from './content/global-variables/main.vue';
    import subscriptionPlanEditor from './content/subscription-plans/main.vue';
    import conditionalScreensEditor from './content/conditional-screens/main.vue';

    export default {
        components: { editorAside, screenEditor, globalVariablesEditor, subscriptionPlanEditor, conditionalScreensEditor },
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
                builder: this.version.builder,
                availableViews: ['Screens', 'Global Variables', 'Subcription Plans', 'Conditional Screens'],
            }
        },
        computed: {
            screenId(){
                return (this.screen || {}).id
            }
        },
        methods: {
            getFirstScreenToShow(){
                
                //  If we have screens
                if( this.builder.screens.length ){

                    //  Get the screen marked as the first screen
                    var markedScreens = this.builder.screens.filter( (screen) => { 
                        return screen.first_display_screen == true;
                    });

                    if( markedScreens.length ){
                     
                        //  Get the first marked screen
                        var firstDisplayScreen = markedScreens[0];

                        //  Return the first display screen
                        return firstDisplayScreen;

                    }

                    //  Otherwise get the first listed screen
                    return this.builder.screens[0];

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
                    this.screen = this.builder.screens[index];

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

                //  Set "Subcription Plans" as the active viewport
                this.handleChangeView('Global Variables');

            },
            handleShowConditionalScreens(){

                //  Set "Conditional Screens" as the active viewport
                this.handleChangeView('Conditional Screens');

            },
        },
        created(){

            //  Get the first screen to show
            this.screen = this.getFirstScreenToShow();
        }
    }
</script>
