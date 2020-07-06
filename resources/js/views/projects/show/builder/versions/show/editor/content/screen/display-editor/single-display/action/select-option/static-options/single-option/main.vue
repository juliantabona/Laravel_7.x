<template>

    <Card :class="cardClasses" :style="cardStyles">
        
        <!-- Static Option Title -->
        <div slot="title" class="cursor-pointer" @click="toggleExpansion()">
            
            <Row>

                <Col :span="14" class="d-flex">

                    <!-- Expand / Collapse Icon  -->
                    <Icon :type="arrowDirection" 
                          class="text-primary cursor-pointer mr-2" :size="20" 
                          :style="{ marginTop: '-3px' }" @click.stop="toggleExpansion()" />

                    <div v-if="option.name.code_editor_mode" class="d-flex">
                        <Icon type="ios-code-working" class="mr-1" :size="20" :style="{ marginTop: '-3px' }" />
                        <span>Code name</span>
                    </div>

                    <template v-else>
                        <!-- If we have a display name  -->
                        <span v-if="option.name.text" class="single-draggable-item-title d-block font-weight-bold cut-text" v-html="option.name.text"></span>
                        
                        <!-- If we don't have a display name  -->
                        <span v-else class="single-draggable-item-title text-danger d-inline-block">No display name</span>
                    </template>

                </Col>

                <Col class="d-flex" :span="10">
                
                    <!-- Failed Link Warning -->
                    <Poptip trigger="hover" width="350" placement="top" word-wrap>

                        <List slot="content" size="small">

                            <ListItem class="p-0">
                                <span class="font-weight-bold mr-1">Active: </span>
                                <span v-if="option.active.code_editor_mode">

                                    <Icon type="ios-code" class="mr-1" size="20" />
                                    <span>Custom Code</span>

                                </span>
                                <template v-else>
                                    <span class="cut-text">{{ option.active.text ? 'Yes' : 'No' }}</span>
                                </template>
                            </ListItem>

                            <ListItem class="p-0">
                                <span class="font-weight-bold mr-1">Name: </span>
                                <span v-if="option.name.code_editor_mode">

                                    <Icon type="ios-code" class="mr-1" size="20" />
                                    <span>Custom Code</span>

                                </span>
                                <template v-else>
                                    <span v-if="option.name.text" v-html="option.name.text" class="cut-text"></span>
                                    <span v-else class="text-danger">No Display Name</span>
                                </template>
                            </ListItem>

                            <ListItem class="p-0">
                                <span class="font-weight-bold mr-1">Value: </span>
                                <span v-if="option.value.code_editor_mode">

                                    <Icon type="ios-code" class="mr-1" size="20" />
                                    <span>Custom Code</span>

                                </span>
                                <template v-else>
                                    <span v-if="option.value.text" v-html="option.value.text" class="cut-text"></span>
                                    <span v-else class="text-info">No Value</span>
                                </template>
                            </ListItem>

                            <ListItem class="p-0">
                                <span class="font-weight-bold mr-1">Input: </span>
                                <span v-if="option.input.code_editor_mode">

                                    <Icon type="ios-code" class="mr-1" size="20" />
                                    <span>Custom Code</span>

                                </span>
                                <template v-else>
                                    <span v-if="option.input.text" v-html="option.input.text" class="cut-text"></span>
                                    <span v-else class="text-danger">No Input</span>
                                </template>
                            </ListItem>

                            <ListItem class="p-0">
                                <span class="font-weight-bold mr-1">Link: </span>
                                <span v-if="option.link.code_editor_mode">
                                    <Icon type="ios-code" class="mr-1" size="20" />
                                    <span>Custom Code</span>
                                </span>
                                <template v-else>
                                    <span v-if="option.link.text" class="d-flex w-100">
                                        <template v-if="isValidLink">
                                            <Icon type="ios-information-circle-outline" class="text-primary mr-1" size="20" />
                                            <span class="text-primary cut-text" v-html="getLinkName"></span>
                                        </template>
                                        <template v-else>
                                            <Icon type="ios-pin-outline" class="text-danger mr-1" size="20" />
                                            <span class="text-danger cut-text" v-html="getLinkName"></span>
                                        </template>
                                    </span>
                                    <span v-else class="text-info">No Link</span>
                                </template>
                            </ListItem>
                            <ListItem v-if="option.comment" class="list-item-comment">
                                <span>
                                    <Icon type="ios-chatbubbles-outline" :size="20" class="mr-1" />
                                    <span class="font-weight-bold">Comment: </span><br>{{ option.comment }}
                                </span>
                            </ListItem>
                        
                        </List>

                        <Icon v-if="isValidOption" type="ios-information-circle-outline" class="text-primary mr-1" :style="{ marginTop: '-5px' }" size="30" />
                        <Icon v-else type="ios-alert-outline" class="text-danger mr-1" :style="{ marginTop: '-5px' }" size="30" />

                    </Poptip>

                    <!-- Active Status -->
                    <div :style="{ marginTop: '-4px' }">
                        <Tag v-if="option.active.code_editor_mode" type="border" color="cyan">Active Conditionally</Tag>
                        <Tag v-else type="border" :color="option.active.text ? 'green' : 'warning'">{{ option.active.text ? 'Active' : 'InActive' }}</Tag>
                    </div>

                </Col>
            </Row>
            
        </div>

        <!-- Static Option Toolbar (Edit, Move, Delete Buttons) -->
        <div slot="extra">

            <div class="single-draggable-item-toolbox">

                <!-- Remove Static Option Button  -->
                <Icon type="ios-trash-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleConfirmRemoveStaticOption()" />

                <!-- Edit Static Option Button  -->
                <Icon type="ios-create-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleEditStaticOption()" />

                <!-- Copy Static Option Button  -->
                <Icon type="ios-copy-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleCloneStaticOption()"/>

                <!-- Move Static Option Button  -->
                <Icon type="ios-move" class="single-draggable-item-icon dragger-handle mr-2" size="20" />
            
            </div> 

        </div>  

        <div v-show="isExpanded">

            <!-- Static Option Details  -->

            <!-- Comment -->
            <span class="d-flex">
                <Icon type="ios-chatbubbles-outline" :size="20" class="mr-1" />
                <span class="font-weight-bold mr-2">Comment: </span><br>
                <span v-if="option.comment">{{ option.comment }}</span>
                <span v-else class="text-info">No comment</span>
            </span>
                
        </div>

        <!-- 
            MODAL TO ADD / CLONE / EDIT STATIC OPTION
        -->
        <template v-if="isOpenManageStaticOptionModal">

            <manageStaticOptionModal
                :index="index"
                :option="option"
                :screen="screen"
                :display="display"
                :builder="builder"
                :options="options"
                :isCloning="isCloning"
                :isEditing="isEditing"
                @visibility="isOpenManageStaticOptionModal = $event">
            </manageStaticOptionModal>

        </template>

    </Card>

</template>

<script>

    import manageStaticOptionModal from './manageStaticOptionModal.vue';

    export default {
        components: { manageStaticOptionModal },
        props: {
            index: {
                type: Number,
                default:null
            },
            display: {
                type: Object,
                default:() => {}
            },
            screen: {
                type: Object,
                default:() => {}
            },
            builder: {
                type: Object,
                default: () => {}
            },
            option: {
                type: Object,
                default:() => {}
            },
            options: {
                type: Array,
                default:() => []
            }
        },
        data(){

            return {
                isOpenManageStaticOptionModal: false,
                isExpanded: false,
                isEditing: false,
                isCloning: false
            }
        },
        computed: {
            cardStyles(){
                return {
                    borderLeft: '4px solid ' + this.option.hexColor
                }
            },
            cardClasses(){
                return [
                    'single-draggable-item', 
                    (this.isExpanded ? 'active' : ''), 'mb-2'
                ]
            },
            arrowDirection(){
                return this.isExpanded ? 'ios-arrow-down' : 'ios-arrow-forward';
            },
            isValidLink(){
                
                //  If the link is not written in code
                if( !this.option.link.code_editor_mode ){
                    
                    //  If the link is provided
                    if( this.option.link.text ){

                        var isScreen = this.option.link.text.startsWith('screen');
                        var isDisplay = this.option.link.text.startsWith('display');

                        //  If we are linking to a screen
                        if( isScreen ){
                            
                            //  If we have a matching screen return true otherwise false
                            var screens = this.builder.screens.filter( (screen) => {
                                
                                return ( screen.id == this.option.link.text ) ? true : false;

                            });

                            if( screens.length ){
                                
                                //  If the screen we are linking to is not the current screen
                                if( this.screen.id != screens[0]['id'] ){

                                    return true;

                                }else{

                                    return false;

                                }

                            }else{

                                return false;

                            }

                        //  If we are linking to a display
                        }else if( isDisplay ){

                            //  If we have a matching display return true otherwise false
                            var displays = this.screen.displays.filter( (display) => {
                                
                                return ( display.id == this.option.link.text ) ? true : false;

                            });
                                
                            if( displays.length ){
                                
                                //  If the display we are linking to is not the current display
                                if( this.display.id != displays[0]['id'] ){

                                    return true;

                                }else{

                                    return false;

                                }

                            }else{

                                return false;

                            }
                        }

                    }

                }

                //  Otherwise return true
                return true;
            },
            isValidOption(){

                //  If the no option name, no option input or invalid link is provided
                if( !this.option.name.code_editor_mode && !this.option.name.text ){
                    return false;
                }else if( !this.option.input.code_editor_mode && !this.option.input.text ){
                    return false;
                }else if( !this.isValidLink ){
                    return false;
                }

                return true;

            },  
            getLinkName(){

                var isScreen = this.option.link.text.startsWith('screen');
                var isDisplay = this.option.link.text.startsWith('display');

                //  If we are linking to a screen
                if( isScreen ){
                    
                    //  If we have a matching screen return true otherwise false
                    var screens = this.builder.screens.filter( (screen) => {
                        
                        return ( screen.id == this.option.link.text ) ? true : false;

                    });

                    if( screens.length ){
                        
                        //  If the screen we are linking to is not the current screen
                        if( this.screen.id != screens[0]['id'] ){

                            return 'Screens / ' + screens[0]['name'];

                        }else{

                            return 'Screens / ' + screens[0]['name'] + '<br>Linking to the same screen';

                        }

                    }else{
                        return 'Screens / ' + 'Unknown Screen';
                    }

                //  If we are linking to a display
                }else if( isDisplay ){

                    //  If we have a matching display return true otherwise false
                    var displays = this.screen.displays.filter( (display) => {
                        
                        return ( display.id == this.option.link.text ) ? true : false;

                    });
                        
                    if( displays.length ){
                        
                        //  If the display we are linking to is not the current display
                        if( this.display.id != displays[0]['id'] ){

                            return 'Displays / ' + displays[0]['name'];

                        }else{

                            return 'Screens / ' + displays[0]['name'] + '<br>Linking to the same display';

                        }

                    }else{
                        return 'Displays / ' + 'Unknown Display';
                    }
                }
            },
        },
        methods: {
            toggleExpansion(){
                this.isExpanded = !this.isExpanded;
            },
            handleEditStaticOption(){
                this.isCloning = false;
                this.isEditing = true;
                this.handleOpenManageStaticOptionModal();
            },
            handleCloneStaticOption() {
                this.isCloning = true;
                this.isEditing = false;
                this.handleOpenManageStaticOptionModal();
            },
            handleConfirmRemoveStaticOption(){

                const self = this;

                if( self.option.code_editor_mode ){
                    var name = 'Custom Option';
                }else{
                    var name = self.option.name.text;
                }

                //  Make a popup confirmation modal so that we confirm the static option removal
                this.$Modal.confirm({
                    width: '450',
                    closable: true,
                    okText: 'Delete',
                    cancelText: 'Cancel',
                    title: 'Delete Static Option',
                    onOk: () => { this.handleRemoveStaticOption() },
                    render: function (h) {
                        return h(
                            'span', [
                                'Are you sure you want to delete "',
                                h('span', { class: ['font-weight-bold'] }, name),
                                '". After this static option is deleted you cannot recover it again.'
                            ]
                        )
                    }
                });
            },
            handleRemoveStaticOption() {

                //  Remove static option from list
                this.options.splice(this.index, 1);

                //  Option removed success message
                this.$Message.success({
                    content: 'Static option removed!',
                    duration: 6
                });
            },
            handleOpenManageStaticOptionModal(){
                this.isOpenManageStaticOptionModal = true;
            }
        }
    }

</script>
