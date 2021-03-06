<template>

    <Card :class="cardClasses" :style="cardStyles">
        
        <!-- Display Title (Name & First Display Checkbox) -->
        <div slot="title" :class="[ isEditing ? 'd-flex' : '' ]">

            <template v-if="isEditing">

                <!-- Display name input (Changes the display name) -->  
                <Input type="text" v-model="display.name" placeholder="Name" class="w-50 mr-3" 
                        maxlength="50" show-word-limit @keyup.enter.native="handleSubmit()">
                        <span slot="prepend">Name</span>
                </Input>

                <!-- If we support manual selection of the first display -->  
                <template v-if="!screen.conditional_displays.active">
                    
                    <!-- Show first display checkbox (Marks the display as the first display) -->
                    <Checkbox 
                        v-model="display.first_display"
                        :disabled="display.first_display" class="mt-2"
                        @on-change="handleMarkedAsFirstDisplay($event)">
                        First Display
                    </Checkbox>
                
                </template>

            </template>

            <!-- Display Name Label  -->
            <Row :gutter="12" v-else>
                <Col :span="16">
                    <span class="d-block font-weight-bold cut-text">
                        {{ getDisplayNumber + '. ' }}
                        {{ display.name }}
                    </span>
                </Col>
                <Col :span="8">
                    <Icon type="ios-camera-outline" class="text-primary" :size="20" />
                </Col>
            </Row>
            
        </div>

        <!-- Display Toolbar (Edit, Move, Delete Buttons) -->
        <div slot="extra">

            <div class="single-draggable-item-toolbox">

                <!-- Remove Display Button  -->
                <Icon type="ios-trash-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleConfirmRemoveDisplay(index)"/>

                <!-- Edit Display Button  -->
                <Icon type="ios-create-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleEditDisplay()" />

                <!-- Copy Display Button  -->
                <Icon type="ios-copy-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleCloneDisplay()"/>

                <!-- Move Display Button  -->
                <Icon v-if="!isFiltered" type="ios-move" class="single-draggable-item-icon dragger-handle mr-2" size="20" />
            
            </div>

            <template v-if="!screen.conditional_displays.active">

                <!-- First Display Pointer -->
                <Icon v-if="display.first_display" type="ios-pin-outline" size="20"  
                    class="first-display-pointer text-success font-weight-bold" />
            
            </template>  

        </div>  

        <!-- Display Content -->                  
        <div v-if="isEditing" class="clearfix mb-3">
            
            <div class="clearfix mb-3">
                <div class="float-right d-flex">
                    <span class="font-weight-bold d-block mt-1 mr-2">Display ID:</span>
                    <ButtonGroup class="mr-2"
                        v-clipboard="display.id"
                        v-clipboard:error="copyIdFail"
                        v-clipboard:success="copyIdSuccess">
                        <Button disabled>
                            <span>{{ display.id }}</span>
                        </Button>
                        <Button type="warning">
                            <Icon type="md-copy"></Icon>
                            Copy ID
                        </Button>
                    </ButtonGroup> 
    
                    <Dropdown trigger="click" placement="bottom-end" class="mt-1">

                        <!-- Show More Icon -->
                        <Icon type="md-more" :size="20"/>

                        <DropdownMenu slot="list">
                            <DropdownItem @click.native="openCopyDisplayPropertiesModal()">Copy Properties</DropdownItem>
                            <DropdownItem @click.native="pasteCopiedProperty()">Paste Properties</DropdownItem>
                        </DropdownMenu>
                    </Dropdown>

                </div>

            </div>

            <!-- Navigation Tabs -->
            <Tabs v-model="activeNavTab" type="card" style="overflow: visible;" :animated="false" name="display-tabs">

                <TabPane v-for="(currentTabName, key) in navTabs" :key="key" :label="currentTabName.name" :name="currentTabName.value"></TabPane>

            </Tabs>

            <!-- Display Instruction -->
            <displayInstruction v-show="activeNavTab == '1'" :display="display"></displayInstruction>

            <!-- Display Action -->
            <displayAction v-show="activeNavTab == '2'" :version="version" :screen="screen" :display="display"></displayAction>

            <!-- Display Events -->
            <displayEvents v-show="activeNavTab == '3'" :globalMarkers="globalMarkers" :version="version" :screen="screen" :display="display"></displayEvents>

            <!-- Display Navigation -->
            <displayNavigation v-show="activeNavTab == '4'" :version="version" :screen="screen" :display="display"></displayNavigation>

            <!-- Display Pagination -->
            <displayPagination v-show="activeNavTab == '5'" :version="version" :screen="screen" :display="display"></displayPagination>

        </div>

        <div class="border-top pt-3">
            <span class="d-inline-block mr-2">
                <span class="font-weight-bold">Highlighter</span>: 
                <ColorPicker v-model="display.hexColor" recommend></ColorPicker>
            </span>
        </div>

        <!-- 
            MODAL TO CLONE DISPLAY
        -->
        <template v-if="isOpenAddDisplayModal">

            <addDisplayModal
                :screen="screen"
                :display="display"
                :version="version"
                @visibility="isOpenAddDisplayModal = $event">
            </addDisplayModal>

        </template>

        <!-- 
             MODAL TO OPEN COPY DISPLAY PROPERTIES MODAL
        -->
        <template v-if="isOpenCopyDisplayPropertiesModal">

            <copyDisplayPropertiesModal
                :display="display"
                @visibility="isOpenCopyDisplayPropertiesModal = $event">
            </copyDisplayPropertiesModal>

        </template>

    </Card>

</template>

<script>

    import copyDisplayPropertiesModal from './copyDisplayPropertiesModal';

    import addDisplayModal from './../addDisplayModal.vue';

    //  Get the display instruction
    import displayInstruction from './instruction/main.vue';

    //  Get the display pagination
    import displayPagination from './pagination/main.vue';

    //  Get the display navigation
    import displayNavigation from './navigation/main.vue';

    //  Get the display action
    import displayAction from './action/main.vue';

    //  Get the display events
    import displayEvents from './events/main.vue';

    export default {
        components: { 
            copyDisplayPropertiesModal, addDisplayModal, displayInstruction,
            displayPagination, displayNavigation, displayAction, displayEvents
        },
        props: {
            index: {
                type: Number,
                default: null
            },
            screen: {
                type: Object,
                default: null
            },
            display: {
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
            },
            isFiltered: {
                type: Boolean,
                default: false
            }
        },
        data(){
            return {
                isEditing: false,
                activeNavTab: '1',
                isOpenAddDisplayModal: false,
                isOpenCopyDisplayPropertiesModal: false,
            }
        },
        computed: {
            totalDisplays(){
                return this.screen.displays.length;
            },
            cardStyles(){
                return {
                        borderLeft: '4px solid ' + this.display.hexColor
                    }
            },
            cardClasses(){
                return [(this.isEditing ? 'active': ''), 'single-draggable-item', 'mb-2']
            },
            getDisplayNumber(){
                /**
                 *  Returns the display number. We use this as we list the displays.
                 *  It works like a counter.
                 */
                return (this.index != null ? this.index + 1 : '');
            },
            eventTabName(){
                
                 var tabName = 'Events';
                 var totalEvents = this.display.content.events.before_reply.length + this.display.content.events.after_reply.length;

                if( totalEvents ){
                    tabName += ' ('+totalEvents+')';
                }

                return tabName;
            },
            navigationTabName(){
                
                 var tabName = 'Navigation';
                 var totalNavigations = this.display.content.screen_repeat_navigation.forward_navigation.length + 
                                        this.display.content.screen_repeat_navigation.backward_navigation.length;

                if( totalNavigations ){
                    tabName += ' ('+totalNavigations+')';
                }

                return tabName;
            },
            paginationTabName(){
                
                 var tabName = 'Pagination';

                if( this.display.content.pagination.active.selected_type == 'conditional' ){
                    tabName += ' (Conditional)';
                }else{
                    if( this.display.content.pagination.active.selected_type == 'yes' ){
                        tabName += ' (On)';
                    }else {
                        tabName += ' (Off)';
                    }
                }

                return tabName;
            },
            navTabs(){
                var tabs = [
                    { name: 'Instruction', value: '1' },
                    { name: 'Action', value: '2' },
                    { name: this.eventTabName, value: '3' }
                ];

                //  If this display supports screen repeatnavigation
                if( this.navigationIsSupported ){
                    tabs.push({ name: this.navigationTabName, value: '4' });
                }
                
                tabs.push({ name: this.paginationTabName, value: '5' });
                tabs.push({ name: 'Settings', value: '6' });

                return tabs;

            },
            navigationIsSupported(){

                return (this.screen.repeat.active.selected_type == 'conditional' || 
                        this.screen.repeat.active.selected_type == 'yes' );
            },
            toggleMenuOptions(){
                
                var tabs = [
                    {
                        name: 'Instruction',
                        property: 'description'
                    },
                    {
                        name: 'Action',
                        property: 'action'
                    }
                ];

                //  If the screen type is "repeat" then add the "Navigation" option
                if( this.navigationIsSupported == 'repeat' ){
                    tabs.push(
                        {
                            name: 'Navigation',
                            property: 'screen_repeat_navigation'
                        }
                    );
                }
            
                tabs.push(
                    {
                        name: 'Events',
                        property: 'events'
                    },
                    {
                        name: 'Pagination',
                        property: 'pagination'
                    }
                );

                return tabs;

            }
        },
        methods: {
            openCopyDisplayPropertiesModal(){
                this.isOpenCopyDisplayPropertiesModal = true;
            },
            handleEditDisplay(){
                this.isEditing = !this.isEditing;
            },
            handleCloneDisplay() {
                this.isOpenAddDisplayModal = true;
            },
            handleConfirmRemoveDisplay(){

                const self = this;

                //  Make a popup confirmation modal so that we confirm the screen removal
                this.$Modal.confirm({
                    width: '450',
                    closable: true,
                    okText: 'Delete',
                    cancelText: 'Cancel',
                    title: 'Delete Display',
                    onOk: () => { this.handleRemoveDisplay() },
                    render: function (h) {
                        return h(
                            'span', [
                                'Are you sure you want to delete "',
                                h('span', { class: ['font-weight-bold'] }, self.display.name),
                                '". After this display is deleted you cannot recover it again.'
                            ]
                        )
                    }
                });
            },
            handleRemoveDisplay() {

                //  Remove display from list
                this.screen.displays.splice(this.index, 1);

                //  Check if we have a display that has been set as the first display
                var firstDisplayExists = this.screen.displays.filter( (display) => { 
                    return display.first_display == true;
                }).length ? true : false;

                //  If we don't have a display that has been set as the first display
                if( !firstDisplayExists ){ 

                    //  If we have any displays
                    if( this.totalDisplays ){

                        //  Set the first display as the first display
                        this.$set(this.screen.displays[0], 'first_display', true);

                    }

                }

                //  Display remove success message
                this.$Message.success({
                    content: 'Display removed!',
                    duration: 6
                });
            },
            handleMarkedAsFirstDisplay(){
                
                //  Foreach display
                for(var x = 0; x < this.screen.displays.length; x++){

                    //  Disable the first display attribute for each display except the current display
                    if( this.screen.displays[x].id != this.display.id){

                        /** Disable first_display attribute so that we only have the current display as
                         *  the only display with a true value
                         */
                        this.screen.displays[x].first_display = false;

                    }else{

                        //  Make sure that the first display attribute for the current display enabled
                        this.screen.displays[x].first_display = true;

                    }
                }
            },
            copyIdSuccess({ value, event }){
                this.$Message.success({
                    content: 'Display ID copied!',
                    duration: 6
                });
            },
            copyIdFail({ value, event }){
                this.$Message.warning({
                    content: 'Could not copy the Display ID',
                    duration: 6
                });
            },
            pasteCopiedProperty(property){
                
                //  Get the display properties from the local storage
                var display_properties = window.localStorage.getItem('display_properties');

                //  Convert String to Object
                display_properties = display_properties ? JSON.parse(display_properties) : null;

                if( display_properties != null ){
                
                    this.display.content = Object.assign({}, this.display.content, display_properties);

                    this.$Message.success({
                        content: 'Pasted to display!',
                        duration: 6
                    });

                }

            }
        }
    }

</script>
