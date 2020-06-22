<template>
  
  <!-- Render the custom component layout -->
  <component v-if="!isLoading" :is="layout">

    <!-- Place the custom route content here -->
    <router-view />
  
  </component>

</template>

<script>

    //  Basic Layout
    import BasicLayout from './layouts/basic/main.vue';
        
    //  Dashboard Layout
    import DashboardLayout from './layouts/dashboard/main.vue';

    export default {
        components: {
            BasicLayout, DashboardLayout
        },
        data() {
            return {
                isLoading: false,
                //  The default layout is the "BasicLayout"
                defaultLayout: 'Basic'
            }
        },
        computed: {
            layout(){
                /** Check the route meta for any defined layout to use.
                 *  If not set use the default basic layout.
                 */
                return (this.$route.meta.layout || this.defaultLayout ) + 'Layout';
            }
        },
        created(){
            
            console.log('App.vue created!');

            //  Hold constant reference to the current Vue instance
            const self = this;

            //  Start loader
            self.isLoading = true;

            /** Handle the authourization process of user auth tokens if the
             *  current route requires an authenticated user
             */
            auth.handleAuthourization(this)
                    .then(() => {

                        console.log('Build layout in App.vue');

                        //  Stop loader
                        self.isLoading = false;
                        
                    }).catch((response) => {

                        //  Stop loader
                        self.isLoading = false;

                    });

        }
    };

</script>