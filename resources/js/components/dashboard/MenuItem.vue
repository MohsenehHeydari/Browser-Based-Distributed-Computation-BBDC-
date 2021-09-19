<template>
    
    <li class="nav-item" :class="$route.meta.resource === route.meta.resource && isMainMenu ? 'menu-open':''">
    
        <!-- like <a> create a link to a address that vue-router makes -->
        <router-link :to="isMainMenu?route.path:{name:route.name}" class="nav-link" :exact-active-class="'active'"> <!--some routes doesnt have name -->
        <i class="nav-icon" :class="route.meta.icon"></i>
        <p>{{route.meta.title}}</p>
        
        <!-- {{route.children}} -->
        <i v-if="hasChildren" class="right fas fa-angle-left"></i>
        </router-link>
                
        <ul v-if="hasChildren" class="nav nav-treeview">
            <sub-menu-item :isMainMenu="false"
            v-for="(child, index) in children" :key="parentIndex+'-'+index"
            :route="child"></sub-menu-item>
        </ul>
    </li>
         
</template>

<script>
    
    export default {
        components:{SubMenuItem: ()=> import ('./MenuItem.vue')},
        data(){
            return{
            
            }
        },
        props:{
            route: Object,
            isMainMenu: Boolean,
            parentIndex: Number
        },
        inject:['type'],
        computed:{
            hasChildren(){
                return this.route.children!== undefined && this.route.children.length > 0;
            },
            children(){
                if(this.hasChildren){
                    return this.route.children.filter((child)=>{
                        return child.meta.visible === true;
                    })
                }
                return [];
            }
        },
        mounted(){
            
        }

    }
</script>
