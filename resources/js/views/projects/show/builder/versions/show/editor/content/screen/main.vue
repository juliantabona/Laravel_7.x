<template>

    <Card :bordered="false">

        <!-- Main Heading -->  
        <Divider orientation="left">
            <span class="font-weight-bold text-dark">Screen Settings</span>
        </Divider>
        
        <!-- Screen Settings -->
        <Row :gutter="20">

            <!-- Screen name & markers -->
            <Col :span="12">

                <!-- Screen name input (Changes the screen name) -->  
                <Input type="text" v-model="screen.name" placeholder="Name" 
                        maxlength="50" show-word-limit @keyup.enter.native="handleSubmit()">
                        <span slot="prepend">Name</span>
                </Input>
                
                <!-- Screen Markers -->  
                <div class="d-flex mt-2">
                    <vue-tags-input 
                        v-model="markerText" :tags="screenMarkers" class="w-100"
                        @tags-changed="addMarker($event)" placeholder="Add marker" 
                        @adding-duplicate="handleAddingDuplicate($event)"
                        :autocomplete-items="globalMarkers"
                        avoid-adding-duplicates/>
                </div>

            </Col>

            <!-- First Display Screen Checkbox -->
            <Col :span="12">
            
                <div class="clearfix mb-2">
                    <div class="float-right d-flex">
                        <span class="font-weight-bold d-block mt-1 mr-2">Screen ID:</span>
                        <ButtonGroup class="mr-2"
                            v-clipboard="screen.id"
                            v-clipboard:error="copyIdFail"
                            v-clipboard:success="copyIdSuccess">
                            <Button disabled>
                                <span>{{ screen.id }}</span>
                            </Button>
                            <Button>
                                <Icon type="md-copy"></Icon>
                                Copy
                            </Button>
                        </ButtonGroup>
                    </div>
                </div>

                <template v-if="!builder.conditional_screens.active">

                    <!-- Enable / Disable First Display Screen -->
                    <Checkbox 
                        v-model="screen.first_display_screen"
                        :disabled="screen.first_display_screen" class="mt-2"
                        @on-change="handleSelectedFirstDisplayScreen($event)">
                        First Screen
                    </Checkbox>

                </template>

            </Col>

            <!-- Navigation Tabs (Additional Settings) -->
            <Col :span="24" class="mt-4">
                
                <Tabs v-model="activeNavTab" type="card" style="overflow: visible;" :animated="false" name="screen-tabs">

                    <!-- Screen Settings Navigation Tabs -->
                    <TabPane v-for="(currentTabName, key) in navTabs" :key="key" :label="currentTabName.name" :name="currentTabName.value"></TabPane>

                </Tabs>
            
                <!-- Screen displays -->
                <displayEditor v-show="activeNavTab == 1" :globalMarkers="globalMarkers" :screen="screen" :builder="builder"></displayEditor>
                
                <!-- Screen displays -->
                <repeatScreenSettings v-show="activeNavTab == 2" :globalMarkers="globalMarkers" :screen="screen" :builder="builder"></repeatScreenSettings>
                
            </Col>

        </Row>

    </Card>

</template>

<script>

    import repeatScreenSettings from './repeat-editor/main.vue';
    import displayEditor from './display-editor/main.vue';
    import VueTagsInput from '@johmun/vue-tags-input';

    export default {
        components: { VueTagsInput, displayEditor, repeatScreenSettings },
        props: {
            screen: {
                type: Object,
                default: null
            },
            builder: {
                type: Object,
                default: null
            },
        },
        data(){
            return {
                markerText: '',
                activeNavTab: '1',
            }
        },
        computed: {
            screenDisplaysTabName(){
                
                 var tabName = 'Screen Displays';
                 var totalDisplays = this.screen.displays.length;

                if( totalDisplays ){
                    tabName += ' ('+totalDisplays+')';
                }

                return tabName;
            },
            repeatTabName(){
                
                 var tabName = 'Repeat Screen';

                if( this.screen.repeat.active.code_editor_mode ){
                    tabName += ' (Conditional)';
                }else{
                    if( this.screen.repeat.active.text ){
                        tabName += ' (Yes)';
                    }else {
                        tabName += ' (No)';
                    }
                }

                return tabName;
            },
            navTabs(){
                return [
                    { name: this.screenDisplaysTabName, value: '1' },
                    { name: this.repeatTabName, value: '2' }
                ];
            },
            globalMarkers(){
                
                //  If we have screens
                if( this.builder.screens.length ){

                    //  Get the screen marked as the first screen
                    var markers = this.builder.screens.map( (screen) => { 
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

            },
            screenMarkers(){
                return this.screen.markers.map(marker => {
                    return {
                        text: marker.name
                    }
                });
            }
        },
        methods: {
            addMarker(tags){

                //  Update the screen markers
                this.screen.markers = tags.map(tag => {
                    return {
                        name: tag.text
                    } 
                });

                //  Update the builder markers
            },
            copyIdSuccess({ value, event }){
                this.$Message.success({
                    content: 'Screen ID copied!',
                    duration: 6
                });
            },
            copyIdFail({ value, event }){
                this.$Message.warning({
                    content: 'Could not copy the Screen ID',
                    duration: 6
                });
            },
            handleAddingDuplicate(tag){
                this.$Message.warning({
                    content: 'Marker "'+tag.text+'" already exists.',
                    duration: 6
                });
            },
            handleSelectedFirstDisplayScreen(event){
                
                //  Foreach screen
                for(var x = 0; x < this.builder.screens.length; x++){

                    //  Disable the first display screen attribute for each screen except the current screen
                    if( this.builder.screens[x].id != this.screen.id){

                        /** Disable first_display_screen attribute so that we only have the current screen as
                         *  the only screen with a true value
                         */
                        this.builder.screens[x].first_display_screen = false;

                    }else{

                        //  Make sure that the first display screen attribute for the current screen enabled
                        this.builder.screens[x].first_display_screen = true;

                    }
                }
            }
        },
    }
</script>
