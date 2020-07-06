<template>

    <div>

        <!-- Navigation List & Dragger  -->
        <draggable
            :list="navigations"
            @start="drag=true" 
            @end="drag=false" 
            :options="{
                group:'navigations', 
                handle:'.dragger-handle',
                draggable:'.single-draggable-item',
            }">

            <!-- Single Navigation  -->
            <singleNavigation v-for="(navigation, index) in navigations" :key="navigation.id+'_'+index"
                :navigations="navigations"
                :navigation="navigation"
                :builder="builder"
                :display="display"
                :screen="screen"
                :index="index">
            </singleNavigation>
            
            <!-- No navigations message -->
            <Alert v-if="!navigationsExist" type="info" class="mb-0" style="width:300px;" show-icon>No Navigations Found</Alert>

        </draggable>

        <div class="clearfix">

            <!-- Add Static Option Button -->
            <basicButton :type="addButtonType" size="default" icon="ios-add" :showIcon="true"
                            class="float-right" :ripple="!navigationsExist"
                            @click.native="handleAddNavigation()">
                <span>Add Navigation</span>
            </basicButton>

        </div>

        <!-- 
            MODAL TO ADD / CLONE / EDIT NAVIGATION
        -->
        <template v-if="isOpenManageNavigationModal">

            <manageNavigationModal
                :screen="screen"
                :display="display"
                :builder="builder"
                :isCloning="false"
                :isEditing="false"
                :navigations="navigations"
                @visibility="isOpenManageNavigationModal = $event">
            </manageNavigationModal>
    
        </template>

    </div>

</template>

<script>

    import draggable from 'vuedraggable';
    import singleNavigation from './single-navigation/main.vue';
    import manageNavigationModal from './single-navigation/manageNavigationModal.vue';
    import basicButton from './../../../../../../../../../../../../../components/_common/buttons/basicButton.vue';

    export default {
        components: { draggable, singleNavigation, manageNavigationModal, basicButton },
        props: { 
            navigations: {
                type: Array,
                default: () => []
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
            }
        },
        data(){
            return {
                isOpenManageNavigationModal: false,
            }
        },
        computed: {
            totalNavigations(){
                return this.navigations.length;
            },
            navigationsExist(){
                return this.totalNavigations ? true : false;
            },
            addButtonType(){
                return this.navigationsExist ? 'default' : 'success';
            }
        },
        methods: {
            handleAddNavigation(){
                this.isOpenManageNavigationModal = true;
            }
        }
    };
  
</script>