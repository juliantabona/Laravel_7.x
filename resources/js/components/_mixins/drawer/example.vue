<template>
    <div>
        <!-- Drawer 

             Note: drawerVisible and detectClose() are imported from the drawerMixin.
             They are used to allow for opening and closing the drawer properly
             during the v-if conditional statement of the parent component. It
             is important to note that <Drawer> does not open/close well with
             v-if statements by default, therefore we need to add additional
             functionality to enhance the experience. Refer to drawerMixin.
        -->
        <Drawer
            okText="Clone" 
            @on-ok="clone()"
            cancelText="Cancel"
            title="Clone Screen"
            v-model="drawerVisible"
            @on-visible-change="detectClose">
               
            This is the drawer content

        </Drawer>
        
    </div>
</template>
<script>

    import drawerMixin from './main.vue';

    export default {
        mixins: [ drawerMixin ],
        data(){
            return{
                
            }
        },
        methods: {
            /** Note the closeDrawer() method is imported from the
             *  drawerMixin file. It handles the closing process 
             *  of the drawer
             */
            clone(){
                this.$emit('clone');
                this.closeDrawer();
            }
        }
    }
</script>