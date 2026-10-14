let producto={
    nombre: "membresia",
    precio: 10000,
    disponible: "esta disponible",
    categoria: "estudiantil",
}
console.log(producto);

console.log(producto.nombre);
console.log(producto.precio);
console.log(producto.disponible);
console.log(producto.Nombre);
console.log(producto.precio=15000);

const formatearPrecio = (objeto) => {
    return "$" + objeto.precio;
};

console.log(formatearPrecio(producto));