<template>

    <div :class="[(screen.name == (activeScreen || {}).name ? 'active ' : ''), 'screen-menu-item cursor-pointer']" 
         @click="handleSelectedScreen(index)">

        <!-- Screen menu name  -->
        <span class="screen-menu-item-name d-block cut-text">
            {{ screen.name }}
        </span>

        <!-- First Display Screen Pointer -->
        <Icon v-if="screen.first_display_screen && !builder.conditional_screens.active" 
                type="ios-pin-outline" size="20" class="text-success font-weight-bold" 
                :style="{ position: 'absolute', top: '5px', right: '5px' }" />


        <!-- Screen menu toolbox  -->
        <div class="screen-menu-item-toolbox">

            <!-- Remove Screen Button  -->
            <Icon type="ios-trash-outline" class="screen-menu-item-icon hidable mr-1" size="20" @click="handleConfirmRemoveScreen(index)"/>

            <!-- Copy Screen Button  -->
            <Icon type="ios-copy-outline" class="screen-menu-item-icon mr-1" size="20" @click="handleCloneScreen()"/>

            <!-- Move Screen Button  -->
            <Icon type="ios-move" class="screen-menu-item-icon dragger-handle mr-0" size="20" />
        
        </div>

        <!-- 
            MODAL TO CLONE SCREEN
        -->
        <template v-if="isOpenAddScreenModal">

            <addScreenModal
                :screen="screen"
                :builder="builder"
                @selectedScreen="handleSelectedScreen($event)"
                @visibility="isOpenAddScreenModal = $event">
            </addScreenModal>

        </template>

    </div>

</template>

<script>

    import addScreenModal from './../addScreenModal.vue';

    export default {
        components: { addScreenModal },
        props:{
            index: {
                type: Number,
                default:null
            },
            screen: {
                type: Object,
                default:() => {}
            },
            builder: {
                type: Object,
                default: () => {}
            },
            activeScreen: {
                type: Object,
                default:() => {}
            },
        }, 
        data(){
            return {
                isOpenAddScreenModal: false
            }
        },
        computed: {
            totalScreens(){
                return this.builder.screens.length;
            }
        },
        methods: {
            handleSelectedScreen(index) {
                
                //  If the index is set to null
                if( index === null ) {

                    //  Send an update to unselect any active screen
                    this.$emit('selectedScreen', index);

                //  If the user selected any screen menu accept the current active menu
                }else if( (this.builder.screens[index] || {}).id != (this.activeScreen || {}).id ){

                    //  Send an update of the selected screen
                    this.$emit('selectedScreen', index);

                }

                //  Always make sure we are showing the "Screens" viewport
                this.$emit('showScreens');

            },
            handleCloneScreen() {
                this.isOpenAddScreenModal = true;
            },
            handleConfirmRemoveScreen(){

                const self = this;

                //  Make a popup confirmation modal so that we confirm the screen removal
                this.$Modal.confirm({
                    width: '450',
                    closable: true,
                    okText: 'Delete',
                    cancelText: 'Cancel',
                    title: 'Delete Screen',
                    onOk: () => { this.handleRemoveScreen() },
                    render: function (h) {
                        return h(
                            'span', [
                                'Are you sure you want to delete "',
                                h('span', { class: ['font-weight-bold'] }, self.screen.name),
                                '". After this screen is deleted you cannot recover it again.'
                            ]
                        )
                    }
                });
            },
            handleRemoveScreen() {

                var deletingActiveScreen = false;

                //  If the screen being deleted is the current active screen
                if( (this.activeScreen || {}).id == this.builder.screens[this.index].id ){

                    deletingActiveScreen = true;

                }

                //  Remove screen from list
                this.builder.screens.splice(this.index, 1);

                //  Check if we have a screen that has been set as the first display screen
                var firstDisplayScreenExists = this.builder.screens.filter( (screen) => { 
                    return screen.first_display_screen == true;
                }).length ? true : false;

                //  If we don't have a screen that has been set as the first display screen
                if( !firstDisplayScreenExists ){ 

                    //  If we have any screens
                    if( this.totalScreens ){

                        //  Set the first screen as the first display screen
                        this.$set(this.builder.screens[0], 'first_display_screen', true);

                    }

                }

                //  If we have any screens
                if( this.totalScreens ){
                    
                    //  If we are deleting the current active screen
                    if( deletingActiveScreen ){

                        //  Set the first screen as the default active screen
                        this.handleSelectedScreen(0);

                    }

                }else{

                    //  Unset the active screen
                    this.handleSelectedScreen(null);

                }

                //  Screen remove success message
                this.$Message.success({
                    content: 'Screen removed!',
                    duration: 6
                });
            }
        }
    }

</script>