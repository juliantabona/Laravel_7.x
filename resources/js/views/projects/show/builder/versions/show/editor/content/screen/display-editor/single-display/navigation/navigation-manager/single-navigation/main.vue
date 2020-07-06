<template>

    <Card :class="cardClasses" :style="cardStyles">
        
        <!-- Navigation Title -->
        <div slot="title" class="cursor-pointer" @click="toggleExpansion()">
            
            <Row>

                <Col :span="14" class="d-flex">

                    <!-- Expand / Collapse Icon  -->
                    <Icon :type="arrowDirection" 
                          class="text-primary cursor-pointer mr-2" :size="20" 
                          :style="{ marginTop: '-3px' }" @click.stop="toggleExpansion()" />
                          
                    <!-- Navigation name  -->
                    <span class="single-draggable-item-title d-block font-weight-bold cut-text">
                        {{ navigation.name }}
                    </span>
                        
                </Col>

                <Col class="d-flex" :span="10">
                
                    <!-- Failed Link Warning -->
                    <Poptip trigger="hover" width="350" placement="top" word-wrap>

                        <List slot="content" size="small">

                            <ListItem class="p-0">
                                <span class="font-weight-bold mr-1">Active: </span>
                                <span v-if="navigation.active.code_editor_mode">

                                    <Icon type="ios-code" class="mr-1" size="20" />
                                    <span>Custom Code</span>

                                </span>
                                <template v-else>
                                    <span class="cut-text">{{ navigation.active.text ? 'Yes' : 'No' }}</span>
                                </template>
                            </ListItem>

                            <ListItem class="p-0">
                                <span class="font-weight-bold mr-1">Name: </span>
                                <span class="cut-text">{{ navigation.name }}</span>
                            </ListItem>

                            <!-- Navigation Type -->
                            <ListItem class="p-0">
                                <span class="font-weight-bold mr-1">Type: </span>
                                <span class="cut-text">{{ navigationType }}</span>
                            </ListItem>

                            <template v-if="navigation.selected_type == 'custom'">

                                <!-- Navigation Custom Inputs -->
                                <ListItem class="p-0">
                                    <span class="font-weight-bold mr-1">Input(s): </span>
                                    <span v-if="navigation.custom.inputs.code_editor_mode">

                                        <Icon type="ios-code" class="mr-1" size="20" />
                                        <span>Custom Code</span>

                                    </span>
                                    <template v-else>
                                        <span v-if="navigation.custom.inputs.text" 
                                            v-html="navigation.custom.inputs.text" class="cut-text"></span>
                                        <span v-else class="text-danger">No Input Provided</span>
                                    </template>
                                </ListItem>

                                <!-- Navigation Custom Step -->
                                <ListItem class="p-0">
                                    <span class="font-weight-bold mr-1">Step: </span>
                                    <span v-if="navigation.custom.step.code_editor_mode">

                                        <Icon type="ios-code" class="mr-1" size="20" />
                                        <span>Custom Code</span>

                                    </span>
                                    <template v-else>
                                        <span v-if="navigation.custom.step.text" 
                                            v-html="navigation.custom.step.text" class="cut-text"></span>
                                        <span v-else class="text-danger">No Step Provided</span>
                                    </template>
                                </ListItem>

                            </template>

                            <template v-if="navigation.selected_type == 'regex'">

                                <!-- Navigation Regex Pattern -->
                                <ListItem class="p-0">
                                    <span class="font-weight-bold mr-1">Regex Pattern: </span>
                                    <span v-if="navigation.regex.rule.code_editor_mode">

                                        <Icon type="ios-code" class="mr-1" size="20" />
                                        <span>Custom Code</span>

                                    </span>
                                    <template v-else>
                                        <span v-if="navigation.regex.rule.text" 
                                            v-html="navigation.regex.rule.text" class="cut-text"></span>
                                        <span v-else class="text-danger">No Regex Pattern</span>
                                    </template>
                                </ListItem>

                                <!-- Navigation Regex Step -->
                                <ListItem class="p-0">
                                    <span class="font-weight-bold mr-1">Step: </span>
                                    <span v-if="navigation.regex.step.code_editor_mode">

                                        <Icon type="ios-code" class="mr-1" size="20" />
                                        <span>Custom Code</span>

                                    </span>
                                    <template v-else>
                                        <span v-if="navigation.regex.step.text" 
                                            v-html="navigation.regex.step.text" class="cut-text"></span>
                                        <span v-else class="text-danger">No Step Provided</span>
                                    </template>
                                </ListItem>

                            </template>
                            
                            <ListItem v-if="navigation.comment" class="list-item-comment">
                                <span>
                                    <Icon type="ios-chatbubbles-outline" :size="20" class="mr-1" />
                                    <span class="font-weight-bold">Comment: </span><br>{{ navigation.comment }}
                                </span>
                            </ListItem>
                        
                        </List>

                        <Icon v-if="isValidNavigation" type="ios-information-circle-outline" class="text-primary mr-1" :style="{ marginTop: '-5px' }" size="30" />
                        <Icon v-else type="ios-alert-outline" class="text-danger mr-1" :style="{ marginTop: '-5px' }" size="30" />

                    </Poptip>

                    <!-- Active Status -->
                    <div :style="{ marginTop: '-4px' }">
                        <Tag v-if="navigation.active.code_editor_mode" type="border" color="cyan">Active Conditionally</Tag>
                        <Tag v-else type="border" :color="navigation.active.text ? 'green' : 'warning'">{{ navigation.active.text ? 'Active' : 'InActive' }}</Tag>
                    </div>

                </Col>
            </Row>
            
        </div>

        <!-- Navigation Toolbar (Edit, Move, Delete Buttons) -->
        <div slot="extra">

            <div class="single-draggable-item-toolbox">

                <!-- Remove Navigation Button  -->
                <Icon type="ios-trash-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleConfirmRemoveNavigation()" />

                <!-- Edit Navigation Button  -->
                <Icon type="ios-create-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleEditNavigation()" />

                <!-- Copy Navigation Button  -->
                <Icon type="ios-copy-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleCloneNavigation()"/>

                <!-- Move Navigation Button  -->
                <Icon type="ios-move" class="single-draggable-item-icon dragger-handle mr-2" size="20" />
            
            </div> 

        </div>  

        <div v-show="isExpanded">

            <!-- Navigation Details  -->

            <!-- Comment -->
            <span class="d-flex">
                <Icon type="ios-chatbubbles-outline" :size="20" class="mr-1" />
                <span class="font-weight-bold mr-2">Comment: </span><br>
                <span v-if="navigation.comment">{{ navigation.comment }}</span>
                <span v-else class="text-info">No comment</span>
            </span>
                
        </div>

        <!-- 
            MODAL TO ADD / CLONE / EDIT NAVIGATION
        -->
        <template v-if="isOpenManageNavigationModal">

            <manageNavigationModal
                :index="index"
                :screen="screen"
                :display="display"
                :builder="builder"
                :isCloning="isCloning"
                :isEditing="isEditing"
                :navigation="navigation"
                :navigations="navigations"
                @visibility="isOpenManageNavigationModal = $event">
            </manageNavigationModal>

        </template>

    </Card>

</template>

<script>

    import manageNavigationModal from './manageNavigationModal.vue';
    var localCustomMixin = require('./../localMixin.js').default;

    export default {
        components: { manageNavigationModal },
        mixins: [localCustomMixin],
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
            navigation: {
                type: Object,
                default:() => {}
            },
            navigations: {
                type: Array,
                default:() => []
            }
        },
        data(){

            return {
                /** Note: The "navigationTypes" property is located
                 *  within the "localCustomMixin"
                 */
                isOpenManageNavigationModal: false,
                isExpanded: false,
                isEditing: false,
                isCloning: false
            }
        },
        computed: {
            cardStyles(){
                return {
                    borderLeft: '4px solid ' + this.navigation.hexColor
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
            isValidNavigation(){

                if( ( this.navigation.selected_type == 'custom' 
                      && !this.navigation.custom.inputs.text
                      && !this.navigation.custom.inputs.code_editor_mode ) ||
                    ( this.navigation.selected_type == 'custom' 
                      && !this.navigation.custom.step.text
                      && !this.navigation.custom.step.code_editor_mode )   ||
                    ( this.navigation.selected_type == 'regex' 
                      && !this.navigation.regex.rule.text
                      && !this.navigation.regex.rule.code_editor_mode )    ||
                    ( this.navigation.selected_type == 'regex' 
                      && !this.navigation.regex.step.text
                      && !this.navigation.regex.step.code_editor_mode ) ){
                        
                        return false;
                }

                return true;

            },
            navigationType(){
                //  Note: The "navigationTypes" properties are defined within the "localCustomMixin"
                for (let index = 0; index < this.navigationTypes.length; index++) {
                    
                    if( this.navigationTypes[index]['value'] == this.navigation.selected_type){
                        
                        return this.navigationTypes[index]['name'];

                    }
                    
                }
            },
        },
        methods: {
            toggleExpansion(){
                this.isExpanded = !this.isExpanded;
            },
            handleEditNavigation(){
                this.isCloning = false;
                this.isEditing = true;
                this.handleOpenManageNavigationModal();
            },
            handleCloneNavigation() {
                this.isCloning = true;
                this.isEditing = false;
                this.handleOpenManageNavigationModal();
            },
            handleConfirmRemoveNavigation(){

                const self = this;

                //  Make a popup confirmation modal so that we confirm the navigation removal
                this.$Modal.confirm({
                    width: '450',
                    closable: true,
                    okText: 'Delete',
                    cancelText: 'Cancel',
                    title: 'Delete Navigation',
                    onOk: () => { this.handleRemoveNavigation() },
                    render: function (h) {
                        return h(
                            'span', [
                                'Are you sure you want to delete "',
                                h('span', { class: ['font-weight-bold'] }, self.navigation.name),
                                '". After this navigation is deleted you cannot recover it again.'
                            ]
                        )
                    }
                });
            },
            handleRemoveNavigation() {

                //  Remove navigation from list
                this.navigations.splice(this.index, 1);

                //  Navigation removed success message
                this.$Message.success({
                    content: 'Navigation removed!',
                    duration: 6
                });
            },
            handleOpenManageNavigationModal(){
                this.isOpenManageNavigationModal = true;
            }
        }
    }

</script>
