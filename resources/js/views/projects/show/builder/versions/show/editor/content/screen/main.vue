<template>

    <Card :bordered="false">

        <!-- Main Heading -->  
        <Divider orientation="left">
            <span class="font-weight-bold text-dark">Screen Settings</span>
        </Divider>
        
        <!-- Screen Settings -->
        <Row :gutter="20">

            <!-- Screen name & markers -->
            <Col :span="14">

                <!-- Screen name input (Changes the screen name) -->  
                <Input type="text" v-model="screen.name" placeholder="Name" 
                        maxlength="30" show-word-limit @keyup.enter.native="handleSubmit()">
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
            <Col :span="8">

                <!-- Enable / Disable First Display Screen -->
                <Checkbox 
                    v-model="screen.first_display_screen"
                    :disabled="screen.first_display_screen" class="mt-2"
                    @on-change="handleSelectedFirstDisplayScreen($event)">
                    First Screen
                </Checkbox>

            </Col>

            <!-- Navigation Tabs (Additional Settings) -->
            <Col :span="24" class="mt-4">

                <Tabs v-model="activeNavTab" type="card" style="overflow: visible;" :animated="false">

                    <!-- Screen Settings Navigation Tabs -->
                    <TabPane v-for="(currentTabName, key) in navTabs" :key="key" :label="currentTabName" :name="currentTabName"></TabPane>

                </Tabs>
            
                <!-- Screen displays -->
                <displayEditor v-show="activeNavTab == 'Screen Displays'" :screen="screen" :builder="builder"></displayEditor>
                
            </Col>

        </Row>

    </Card>

</template>

<script>

    //  Inport Vue Input Tags
    import VueTagsInput from '@johmun/vue-tags-input';

    //  Get the display editor
    import displayEditor from './display-editor/main.vue';

    export default {
        components: { VueTagsInput, displayEditor },
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
                activeNavTab: 'Screen Displays',
            }
        },
        computed: {
            navTabs(){
                var tabs = ['Screen Displays', 'Advanced'];

                //  If the screen type is "repeat" then add the "Repeat Events" tabs
                if( this.screen.type.selected_type == 'repeat' ){
                    tabs.push('Repeat Events');
                    tabs.push('Repeat Settings');
                }

                return tabs;
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
