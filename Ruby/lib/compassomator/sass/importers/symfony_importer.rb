module Sass
  module Importers
    class SymfonyImporter < Filesystem

        attr_accessor :bundle_name, :bundle_reference_name

        def initialize(root, bundle_name)
            super(root+"/Resources")
            @bundle_name = bundle_name
            @bundle_reference_name = "@#{bundle_name}"
        end

        def find(name, options)
            if is_bundle_reference(name)
                # replace bundle reference name with trailing slash
                name = name.gsub(@bundle_reference_name+'/', '')
                _find(@root, name, options)
            else
                nil
            end
        end

        def is_bundle_reference(name)
            name.start_with?(@bundle_reference_name)
        end

    end
  end
end