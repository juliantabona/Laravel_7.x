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
                <Input type="text" v-model="localScreen.name" placeholder="Name" 
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
                            v-clipboard="localScreen.id"
                            v-clipboard:error="copyIdFail"
                            v-clipboard:success="copyIdSuccess">
                            <Button disabled>
                                <span>{{ localScreen.id }}</span>
                            </Button>
                            <Button>
                                <Icon type="md-copy"></Icon>
                                Copy
                            </Button>
                        </ButtonGroup>
    
                        <Dropdown trigger="click" placement="bottom-end" class="mt-1">

                            <!-- Show More Icon -->
                            <Icon type="md-more" :size="20"/>

                            <DropdownMenu slot="list">
                                <DropdownItem @click.native="openCopyScreenPropertiesModal()">Copy Properties</DropdownItem>
                                <DropdownItem @click.native="pasteCopiedProperty()">Paste Properties</DropdownItem>
                            </DropdownMenu>
                        </Dropdown>
                    </div>
                </div>

                <template v-if="!version.builder.conditional_screens.active">

                    <!-- Enable / Disable First Display Screen -->
                    <Checkbox 
                        v-model="localScreen.first_display_screen"
                        :disabled="localScreen.first_display_screen" class="mt-2"
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
                <displayEditor v-show="activeNavTab == 1" :globalMarkers="globalMarkers" :screen="localScreen" :version="version"></displayEditor>
                
                <!-- Screen requirements -->
                <screenRequirements v-show="activeNavTab == 2" :screen="localScreen" :version="version"></screenRequirements>
                
                <!-- Screen displays -->
                <repeatScreenSettings v-show="activeNavTab == 3" :globalMarkers="globalMarkers" :screen="localScreen" :version="version"></repeatScreenSettings>
                
            </Col>

        </Row>

        <!-- 
             MODAL TO OPEN COPY SCREEN PROPERTIES MODAL
        -->
        <template v-if="isOpenCopyScreenPropertiesModal">

            <copyScreenPropertiesModal
                :screen="localScreen"
                @visibility="isOpenCopyScreenPropertiesModal = $event">
            </copyScreenPropertiesModal>

        </template>

    </Card>

</template>

<script>

    import copyScreenPropertiesModal from './copyScreenPropertiesModal';
    import screenRequirements from './requirements/main.vue';
    import repeatScreenSettings from './repeat-editor/main.vue';
    import displayEditor from './display-editor/main.vue';
    import VueTagsInput from '@johmun/vue-tags-input';

    export default {
        components: { copyScreenPropertiesModal, screenRequirements, repeatScreenSettings, displayEditor, VueTagsInput },
        props: {
            screen: {
                type: Object,
                default: null
            },
            version: {
                type: Object,
                default: null
            },
            globalMarkers: {
                type: Array,
                default: () => []
            }
        },
        data(){
            return {
                markerText: '',
                activeNavTab: '1',
                localScreen: this.screen,
                isOpenCopyScreenPropertiesModal: false,
            }
        },
        computed: {
            screenDisplaysTabName(){
                
                 var tabName = 'Screen Displays';
                 var totalDisplays = this.localScreen.displays.length;

                if( totalDisplays ){
                    tabName += ' ('+totalDisplays+')';
                }

                return tabName;
            },
            requiremenentsTabName(){

                var requirementsCount = 0;

                if( this.localScreen.requirements.requires_account.active.selected_type == 'yes' ||
                    this.localScreen.requirements.requires_account.active.selected_type == 'conditional' ){

                    requirementsCount += 1;

                }

                if( this.localScreen.requirements.requires_subscription.active.selected_type == 'yes' ||
                    this.localScreen.requirements.requires_subscription.active.selected_type == 'conditional' ){

                    requirementsCount += 1;

                }

                if( requirementsCount ){
                    var tabName = 'Requirements ('+requirementsCount+')';
                }else{
                    var tabName = 'Requirements';
                }

                return tabName;
            },
            repeatTabName(){
                
                 var tabName = 'Repeat';

                if( this.localScreen.repeat.active.selected_type == 'conditional' ){

                    tabName += ' (Conditional)';

                }else if( this.localScreen.repeat.active.selected_type == 'yes' ){
                    
                    tabName += ' (Yes)';

                }else if( this.localScreen.repeat.active.selected_type == 'no' ){
                    
                    tabName += ' (No)';

                }

                return tabName;
            },
            navTabs(){
                return [
                    { name: this.screenDisplaysTabName, value: '1' },
                    { name: this.requiremenentsTabName, value: '2' },
                    { name: this.repeatTabName, value: '3' }
                ];
            },
            screenMarkers(){
                return this.localScreen.markers.map(marker => {
                    return {
                        text: marker.name
                    }
                });
            }
        },
        methods: {
            openCopyScreenPropertiesModal(){
                this.isOpenCopyScreenPropertiesModal = true;
            },
            pasteCopiedProperty(property){
                
                //  Get the screen properties from the local storage
                var screen_properties = window.localStorage.getItem('screen_properties');

                //  Convert String to Object
                screen_properties = screen_properties ? JSON.parse(screen_properties) : null;

                if( screen_properties != null ){
                
                    this.localScreen = Object.assign({}, this.localScreen, screen_properties);

                    //  Get the index of the current screen
                    var index = this.version.builder.screens.findIndex(screen => screen.id === this.localScreen.id);

                    //  Update the builder screens
                    this.$set(this.version.builder.screens, index, this.localScreen);

                    this.$Message.success({
                        content: 'Pasted to screen!',
                        duration: 6
                    });

                }

            },
            addMarker(tags){

                //  Update the screen markers
                this.localScreen.markers = tags.map(tag => {
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
                for(var x = 0; x < this.version.builder.screens.length; x++){

                    //  Disable the first display screen attribute for each screen except the current screen
                    if( this.version.builder.screens[x].id != this.localScreen.id){

                        /** Disable first_display_screen attribute so that we only have the current screen as
                         *  the only screen with a true value
                         */
                        this.version.builder.screens[x].first_display_screen = false;

                    }else{

                        //  Make sure that the first display screen attribute for the current screen enabled
                        this.version.builder.screens[x].first_display_screen = true;

                    }
                }
            }
        },
    }
</script>
