import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../bloc/field_cubit.dart';

class MapPage extends StatefulWidget {
  const MapPage({Key? key}) : super(key: key);

  @override
  State<MapPage> createState() => _MapPageState();
}

class _MapPageState extends State<MapPage> {
  final MapController _mapController = MapController();

  // Coordonnées par défaut (Dakar, Sénégal par exemple)
  final LatLng _center = const LatLng(14.6928, -17.4467);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Carte des Terrains'),
      ),
      body: BlocBuilder<FieldCubit, FieldState>(
        builder: (context, state) {
          if (state is FieldLoading) {
            return const Center(child: CircularProgressIndicator());
          }
          
          List<dynamic> fields = [];
          if (state is FieldLoaded) {
            fields = state.fields;
          }

          final markers = fields.where((f) => f['latitude'] != null && f['longitude'] != null).map((field) {
            return Marker(
              point: LatLng(
                double.parse(field['latitude'].toString()), 
                double.parse(field['longitude'].toString())
              ),
              width: 50,
              height: 50,
              child: GestureDetector(
                onTap: () {
                  showModalBottomSheet(
                    context: context,
                    builder: (context) {
                      return Container(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(field['name'], style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                            Text("${field['price_per_hour']} FCFA / h"),
                            const SizedBox(height: 16),
                            ElevatedButton(
                              onPressed: () {
                                Navigator.pop(context);
                                context.push('/field/${field['id']}');
                              },
                              child: const Text('Voir le terrain'),
                            )
                          ],
                        ),
                      );
                    }
                  );
                },
                child: const Icon(
                  Icons.location_on, 
                  color: Colors.red, 
                  size: 40,
                ),
              ),
            );
          }).toList();

          return FlutterMap(
            mapController: _mapController,
            options: MapOptions(
              initialCenter: _center,
              initialZoom: 12.0,
            ),
            children: [
              TileLayer(
                urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                userAgentPackageName: 'com.myterrain.app',
              ),
              MarkerLayer(markers: markers),
            ],
          );
        },
      ),
    );
  }
}
